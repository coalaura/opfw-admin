<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const GraphColors = [
        "blue"   => '5383c6',
        "green"  => '66c653',
        "yellow" => 'c6b353',
        "red"    => 'c65353',
        "purple" => '9f53c6',
    ];

    public function rickroll()
    {
        return redirect("https://www.youtube.com/watch?v=dQw4w9WgXcQ");
    }

    /**
     * Returns the next and previous links
     *
     * @param int $page
     * @return array
     */
    protected function getPageUrls(int $page): array
    {
        $url = preg_replace('/[&?]page=\d+/m', '', $_SERVER['REQUEST_URI']);

        if (Str::contains($url, '?')) {
            $url .= '&';
        } else {
            $url .= '?';
        }

        $next = $url . 'page=' . ($page + 1);
        $prev = $url . ($page > 2 ? 'page=' . ($page - 1) : '');

        return [
            'next' => $next,
            'prev' => $prev,
        ];
    }

    /**
     * @param bool $status
     * @param mixed|null $data
     * @param string $error
     * @return Response
     */
    protected static function json(bool $status, $data = null, string $error = ''): Response
    {
        if ($status) {
            $resp = [
                'status' => true,
                'data'   => $data,
            ];
        } else {
            $resp = [
                'status'  => false,
            ];

            if ($error) {
                $resp['message'] = $error;
            }

            if ($data) {
                $resp['data'] = $data;
            }

        }

        return self::jsonRaw($resp);
    }

    /**
     * @param int $status
     * @param string $text
     * @return Response
     */
    protected static function jsonRaw(array $json): Response
    {
        return (new Response($json, 200))->header('Content-Type', 'application/json');
    }

    /**
     * @param int $status
     * @param string $text
     * @return Response
     */
    protected static function text(int $status, string $text): Response
    {
        return (new Response($text, $status))->header('Content-Type', 'text/plain');
    }

    /**
     * @param int $status
     * @param string $text
     * @return Response
     */
    protected static function fakeText(int $status, string $text): Response
    {
        $style = 'html,body{width:100%;background:#1C1B22;color:#fbfbfe;font-family:monospace;font-size:13px;white-space:pre-wrap;margin:0;box-sizing:border-box}body{padding:8px}a{text-decoration:none;color:#909bff}.sup{font-size:10px;vertical-align:top}';

        $text = '<style>' . $style . '</style>' . $text;

        return (new Response($text, $status))->header('Content-Type', 'text/html');
    }

    protected function isSeniorStaff(Request $request): bool
    {
        $player = user();

        return $player && $player->isSeniorStaff();
    }

    protected function isSuperAdmin(Request $request): bool
    {
        $player = user();

        return $player && $player->isSuperAdmin();
    }

    protected function isRoot(Request $request): bool
    {
        $player = user();

        return $player && $player->isRoot();
    }

    private function brighten($rgb, $amount)
    {
        foreach ($rgb as &$color) {
            $color = max(0, min(255, $color + $amount));
        }

        return $rgb;
    }

    protected function renderGraph(array $entries, string $title, array $colors = ["blue"])
    {
        if (!function_exists('imagecreatetruecolor')) {
            return 'GD library is not installed';
        }

        $entries = array_map(function ($entry) {
            if (!is_array($entry)) {
                $entry = [$entry];
            }

            return $entry;
        }, $entries);

        $size       = max(1024, sizeof($entries));
        $entryWidth = floor($size / sizeof($entries));

        $size   = $entryWidth * sizeof($entries);
        $height = floor($size * 0.6);

        $max = ceil(max(array_map(function ($entry) {
            return array_sum($entry);
        }, $entries)) * 1.1);

        $image = imagecreatetruecolor($size, $height);

        $black = imagecolorallocate($image, 28, 27, 34);
        imagefill($image, 0, 0, $black);

        if ($max > 0) {
            for ($g = 0; $g < sizeof($entries[0]); $g++) {
                $key = $colors[$g] ?? 'blue';

                $hex = self::GraphColors[$key];

                $colors[$g] = array_map('hexdec', str_split($hex, 2));
            }

            for ($i = 0; $i < $size; $i++) {
                $entry = $entries[$i] ?? [];

                $y = $height;

                foreach ($entry as $index => $value) {
                    if ($value === 0) {
                        continue;
                    }

                    $percentage = $value / $max;

                    $x = $i * $entryWidth;

                    $x2 = $x + $entryWidth - 1;
                    $y2 = $y - ($height * $percentage);

                    $color = $colors[$index];

                    if ($i % 2 === 0) {
                        $color = $this->brighten($color, 8);
                    } else {
                        $color = $this->brighten($color, -4);
                    }

                    $color = imagecolorallocate($image, $color[0], $color[1], $color[2]);

                    imagefilledrectangle($image, $x, $y, $x2, $y2, $color);

                    $y = $y2;
                }

                if ($entryWidth >= 17) {
                    $m = round(array_sum($entry));

                    if ($m <= 0) {
                        continue;
                    }

                    $p = $y - 12;
                    $x = ($i * $entryWidth) + (($entryWidth / 2.0) - (strlen($m) * 3));

                    $text = imagecolorallocate($image, 255, 220, 220);
                    imagestring($image, 2, $x, $p, $m . "", $text);
                }
            }
        } else {
            $noDataText = imagecolorallocate($image, 231, 177, 177);

            imagestring($image, 4, floor($size / 2) - 26, floor($height / 2), "No Data", $noDataText);
        }

        $text = imagecolorallocate($image, 177, 198, 231);
        imagestring($image, 2, 4, 2, $title, $text);

        if ($entryWidth < 17) {
            $text = imagecolorallocate($image, 255, 220, 220);
            imagestring($image, 2, 3, $height - 14, "0", $text);

            $m = round($max / 1.1);
            $p = $height - ($height * ($m / $max)) - 12;

            $text = imagecolorallocate($image, 255, 220, 220);
            imagestring($image, 2, 3, $p, $m . "", $text);
        }

        ob_start();

        imagepng($image);

        $image_data = ob_get_contents();
        ob_end_clean();

        $image_data_base64 = base64_encode($image_data);

        imagedestroy($image);

        return "data:image/png;base64,{$image_data_base64}";
    }

    /**
     * Search values in a column. Available operators are =, !=, >, <, !, default is LIKE
     */
    protected function searchQuery(Request $request, &$query, string $input, mixed $column)
    {
        $search = $request->input($input);

        if (!$search) {
            return;
        }

        $search = array_map(function ($entry) {
            return trim($entry);
        }, explode('|', $search));

        $query->where(function ($subQuery) use ($search, $column) {
            foreach ($search as $index => $value) {
                $parts = preg_split('/(?<=!=|[!=><])(?!=)/', $value);

                $operator = sizeof($parts) > 1 ? $parts[0] : false;
                $value    = trim($operator ? $parts[1] : $value);

                // No need to search for empty values
                if (!$value) {
                    continue;
                }

                $col = is_callable($column) && !is_string($column) ? $column($value) : $column;

                switch ($operator) {
                    case "!=":
                    case "!":
                    case "=":
                        // These are fine as-is
                        break;
                    case ">":
                    case "<":
                        // Only works with numeric values
                        if (!is_numeric($value)) {
                            continue 2;
                        }

                        break;
                    default:
                        // Why use slow LIKE when we can use fast = ?
                        if ((Str::contains($col, "license") || Str::contains($col, "identifier")) && $this->isFullLicenseIdentifier($value)) {
                            $operator = '=';

                            break;
                        }

                        $operator = 'LIKE';
                        $value    = "%{$value}%";

                        break;
                }

                if ($index === 0) {
                    $subQuery->where($col, $operator, $value);
                } else {
                    $subQuery->orWhere($col, $operator, $value);
                }
            }
        });
    }

    /**
     * Sorts a query given allowed columns, sortby and sortorder
     */
    protected function sortQuery(Request $request, &$query, string $default, array $allowed): array
    {
        $sort = $request->query('sort');
        $order = $request->query('order');

        $name = isset($allowed[$sort]) ? $sort : $default;
        $column = $allowed[$name];

        if ($order === 'desc') {
            $query->orderByDesc($column);
        } else {
            $order = '';

            $query->orderBy($column);
        }

        return [
            'sort' => $name,
            'order' => $order,
        ];
    }

    protected function isFullLicenseIdentifier(string $identifier): bool
    {
        return preg_match('/^(license2?:)[a-z0-9]{40}$/i', $identifier) === 1;
    }

    protected function whereSql($query): ?string
    {
        $sql = $query->toSql();

        preg_match('/(?<=where).+?(?=group by|order by|limit|$)/im', $sql, $matches);

        if (isset($matches[0])) {
            return strtolower($matches[0]);
        }

        return null;
    }
}
