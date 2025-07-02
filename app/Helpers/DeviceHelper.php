<?php
namespace App\Helpers;

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
        "_hdmi_",

        // obscure ones (found in legacy india)
        "_acer_",
        "_douwan_",
        "_capture_",
        "_creative_",
        "_user_facing_",
        "_cam_",
        "_obs_camera",
        "_anker_",
        "_warudocam",
        "_prism_",

        // other ones (found over time)
        "_bose_",
        "virtualcamera",
        "_micusb",
        "_driver_",
        "_dell_",
        "_monitor_",
        "_brio_",
        "_stream_",
        "_mic_",
        "_wecam_",
        "_unitycam_",
        "_e2esoft_",
        "_interface_",
        "_communications_",
        "_jbl_",
        "_camo_",
        "studiocam_",
        "_etronvideo_",
        "_smartcam_",
        "_yamaha_",
        "_pro_",
        "_hoofdtelefoon_",
        "_display_",
        "_avid_mbox_",
        "_minifuse_",
        "_beacn_mix_",
        "_insta360_",
        "_powertoys_",
        "screencapture_",
        "wball_",
        "_rift_",
        "_astro_",
        "_kopfh_rer",
        "_lautsprecher_",
        "_earphones_",
        "_winsafe_",
        "_plantronics_",
        "_loop_back_",
        "_subzero_",
        "_profx_",
        "_mixer_",
        "_micr_",
    ];

    public static function check(array $devices): bool
    {
        $filtered = self::filter($devices);

        return sizeof($filtered) >= 2;
    }

    public static function filter(array $devices): array
    {
        // strip "videoinput_", "audioinput_", etc.
        $devices = array_values(array_filter(array_map(function ($device) {
            return preg_replace('/^(video|audio)(in|out)put_?/m', '', $device);
        }, $devices), function ($device) {
            return $device && strlen($device) >= 5 && ! substr($device, 0, 4) !== "gpu_" && ! preg_match('/^_?[\da-f]{4}_[\da-f]{4}_?$/m', $device);
        }));

        return array_values(array_filter($devices, function ($device) {
            return strlen($device) >= 5 && ! self::has($device);
        }));
    }

    private static function has(string $device): bool
    {
        $device = sprintf('_%s_', $device);

        foreach (self::Wordlist as $word) {
            if (strpos($device, $word) !== false) {
                return true;
            }
        }

        return false;
    }
}

/*
$test = <<<EOF
EOF;

$devices = json_decode("[" . str_replace('""', '", "', $test) . "]", true);

var_dump(DeviceHelper::filter($devices));
//*/
