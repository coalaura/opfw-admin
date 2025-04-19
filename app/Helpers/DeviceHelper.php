<?php
namespace App\Helpers;

use Illuminate\Support\Str;

class DeviceHelper
{
    const Wordlist = [
        "_audio_",
        "_micro",
        "_mikro",
        "_nvidia_",
        "_virtual_",
        "_definition_",
        "_voicemeeter_",
        "_speaker",
        "_realtek_",
        "_headset_",
        "_camera_",
        "_steelseries_",
        "_sonar_",
        "_helicon_",
        "_goxlr_",
        "_angle_",
        "_direct3d11_",
        "_geforce_",
        "_headphones_",
        "_output_",
        "_hyperx_",
        "_webcam_",
        "_digital_",
        "_wireless_",
        "_gaming_",
        "_steam_",
        "_streaming_",
        "_cable_",
        "_broadcast",
        "_input_",
        "_game_",
        "_earphone_",
        "_wave_",
        "_arctis_",
        "_quadcast_",
        "_logitech_",
        "_sound_",
        "_elgato_",
        "_voicemod_",
        "_lsvcam_",
        "_droidcam_",
        "_source_",
        "_airpods_",
        "_razer_",
        "_behringer_",
        "_line_",
        "_yeti_",
        "_classic_",
        "_video_",

        // obscure ones (found in legacy india)
        "_acer_",
        "_douwan_",
        "_capture_",
        "_creative_",
        "_user_facing_",
        "_cam_link_",
        "_obs_camera",
        "_anker_",
        "_warudocam",
        "_prism_",
    ];

    public static function check(array $devices): bool
    {
        // strip "videoinput_", "audioinput_", etc.
        $devices = array_values(array_filter(array_map(function ($device) {
            return preg_replace('/^(video|audio)(in|out)put/m', '', $device);
        }, $devices), function ($device) {
            return $device && strlen($device) >= 5 && ! Str::startsWith($device, "gpu_");
        }));

        if (empty($devices)) {
            return true; // very unusual
        }

        $count = 0;

        foreach ($devices as $device) {
            // like "audiooutput_caeaeae_bccacc_caaaeec_cce0_0_abbbb_0b05_1a52"
            if (! self::has($device)) {
                $count++;
            }
        }

        return $count >= 3;
    }

    private static function has(string $device): bool
    {
        $device = sprintf('_%s_', $device);

        foreach (self::Wordlist as $word) {
            if (Str::contains($device, $word)) {
                return true;
            }
        }

        return false;
    }
}
