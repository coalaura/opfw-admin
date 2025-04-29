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
        "_bose_",
        "virtualcamera",
        "_micusb",
    ];

    public static function check(array $devices): bool
    {
        // strip "videoinput_", "audioinput_", etc.
        $devices = array_values(array_filter(array_map(function ($device) {
            return preg_replace('/^(video|audio)(in|out)put/m', '', $device);
        }, $devices), function ($device) {
            return $device && strlen($device) >= 5 && ! substr($device, 0, 4) !== "gpu_";
        }));

        if (empty($devices)) {
            return true; // very unusual
        }

        $filtered = self::filter($devices);

        return sizeof($filtered) >= 3;
    }

    public static function filter(array $devices): array
    {
        return array_values(array_filter($devices, function($device) {
            return ! self::has($device);
        }));
    }

    private static function has(string $device): bool
    {
        $device = sprintf('_%s_', $device);

        foreach (self::Wordlist as $word) {
            if (mb_strpos($device, $word) !== false) {
                return true;
            }
        }

        return false;
    }
}

/*
$test = <<<EOF
"audioinput_steelseries_sonar_microphone_steelseries_sonar_virtual_audio_device""audioinput_kopfh_rer_bose_qc35_ii_j_bluetooth""audioinput_mikrofon_micusb1_4c4a_4155""videoinput_prprlivevirtualcameradx""videoinput_obs_virtual_camera""audiooutput_steelseries_sonar_gaming_steelseries_sonar_virtual_audio_device""audiooutput_steelseries_sonar_chat_steelseries_sonar_virtual_audio_device""audiooutput_steelseries_sonar_microphone_steelseries_sonar_virtual_audio_device""audiooutput_lautsprecher_steam_streaming_speakers""audiooutput_27g2wg3_nvidia_high_definition_audio""audiooutput_lautsprecher_micusb1_4c4a_4155""audiooutput_kopfh_rer_bose_qc35_ii_j_bluetooth""audiooutput_steelseries_sonar_aux_steelseries_sonar_virtual_audio_device""audiooutput_steelseries_sonar_media_steelseries_sonar_virtual_audio_device"
EOF;

$devices = json_decode("[" . str_replace('""', '", "', $test) . "]", true);

var_dump(DeviceHelper::filter($devices));
//*/