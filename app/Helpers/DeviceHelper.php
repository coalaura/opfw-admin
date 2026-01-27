<?php
namespace App\Helpers;

class DeviceHelper
{
    const Wordlist = [
        "_audio_", // 130831 hits
        "_micro", // 71662 hits
        "_virtual_", // 50611 hits
        "_definition_", // 48668 hits
        "_speaker", // 39687 hits
        "_nvidia_", // 37739 hits
        "_realtek_", // 30524 hits
        "_headset_", // 23556 hits
        "camera_", // 20613 hits
        "_headphones_", // 16011 hits
        "_voicemeeter_", // 14338 hits
        "_steelseries_", // 13791 hits
        "_digital_", // 13091 hits
        "_streaming_", // 12803 hits
        "_wireless_", // 12684 hits
        "_steam_", // 12578 hits
        "_sonar_", // 12368 hits
        "_output_", // 11997 hits
        "_hyperx_", // 9862 hits
        "_gaming_", // 9511 hits
        "_webcam_", // 9192 hits
        "_pro_", // 9175 hits
        "_angle_", // 8964 hits
        "_direct3d11_", // 8961 hits
        "_geforce_", // 7387 hits
        "_voicemod_", // 6858 hits
        "_controller_", // 6230 hits
        "_razer_", // 5533 hits
        "_arctis_", // 5404 hits
        "_earphone_", // 5056 hits
        "_line_", // 4901 hits
        "_input_", // 4831 hits
        "_sound_", // 4384 hits
        "_voice_", // 4260 hits
        "_astro_", // 3889 hits
        "_game_", // 3841 hits
        "_quadcast_", // 3801 hits
        "_cable_", // 3602 hits
        "_logitech_", // 3487 hits
        "_broadcast", // 3463 hits
        "_stereo_", // 3281 hits
        "_yeti_", // 3025 hits
        "_lsvcam_", // 2972 hits
        "_elgato_", // 2769 hits
        "_mikro", // 2763 hits
        "_lg_", // 2758 hits
        "_corsair_", // 2486 hits
        "_helicon_", // 2420 hits
        "_goxlr_", // 2420 hits
        "_stream_", // 2226 hits
        "_mic_", // 2054 hits
        "_wave_", // 1999 hits
        "_cam_", // 1720 hits
        "_stealth_", // 1618 hits
        "_intel_", // 1509 hits
        "_dell_", // 1491 hits
        "_classic_", // 1227 hits
        "_capture_", // 1028 hits
        "_spatial_", // 965 hits
        "_jbl_", // 928 hits
        "_video_", // 872 hits
        "_interface_", // 863 hits
        "_droidcam_", // 861 hits
        "_display_", // 811 hits
        "_driver_", // 723 hits
        "_acer_", // 697 hits
        "_studio_", // 693 hits
        "_brio_", // 650 hits
        "_lautsprecher_", // 584 hits
        "wball_", // 510 hits
        "_airpods_", // 490 hits
        "_gtalare_", // 478 hits
        "_samsung_", // 462 hits
        "_user_facing_", // 458 hits
        "_mixer_", // 454 hits
        "_e2esoft_", // 453 hits
        "_hdmi_", // 443 hits
        "_monitor_", // 412 hits
        "_source_", // 403 hits
        "_parleurs_", // 380 hits
        "_douwan_", // 374 hits
        "_scarlett_", // 339 hits
        "_obs_camera", // 301 hits
        "_quantum_", // 282 hits
        "_hoofdtelefoon_", // 266 hits
        "_kopfh_rer", // 249 hits
        "_camo_", // 247 hits
        "_maono_", // 237 hits
        "_micr_", // 230 hits
        "_smartcam_", // 204 hits
        "_xsplit", // 203 hits
        "_altavoces_", // 181 hits
        "_prism_", // 173 hits
        "_casque_", // 164 hits
        "_k66_", // 157 hits
        "_jabra_", // 142 hits
        "_plantronics_", // 140 hits
        "_nt_usb_", // 131 hits
        "_hodetelefoner_", // 128 hits
        "_fantech_", // 128 hits
        "virtualcam", // 127 hits
        "_meta_", // 122 hits
        "_behringer_", // 114 hits
        "studiocam_", // 104 hits
        "_audient_", // 103 hits
        "_crusher_", // 101 hits
        "_reproduktory_", // 98 hits
        "_rode_", // 91 hits
        "_soundbar_", // 89 hits
        "_bose_", // 88 hits
        "_soundcore_", // 87 hits
        "_thronmax_", // 72 hits
        "_auriculares_", // 72 hits
        "_live_streamer_", // 70 hits
        "_avermedia_", // 66 hits
        "_eksa_", // 65 hits
        "_creative_", // 64 hits
        "_earphones_", // 62 hits
        "_obsbot_", // 61 hits
        "_rift_", // 53 hits
        "_conexant_", // 50 hits
        "_communications_", // 49 hits
        "_nos_", // 46 hits
        "_etronvideo_", // 45 hits
        "_loop_back_", // 44 hits
        "_warudocam", // 44 hits
        "_anker_", // 42 hits
        "_beacn_mix_", // 39 hits
        "_soundcard_", // 38 hits
        "_vcam_", // 35 hits
        "_yamaha_", // 34 hits
        "_insta360_", // 30 hits
        "_minifuse_", // 29 hits
        "_nuroum_", // 28 hits
        "_motu_", // 24 hits
        "_usb_dac_", // 24 hits
        "_fineshare_", // 17 hits
        "_micusb", // 14 hits
        "_viro_plus_", // 13 hits
        "_chromacam_", // 12 hits
        "_rc_505", // 12 hits
        "_katana_", // 11 hits
        "_avstream_", // 10 hits
        "_wistream_", // 10 hits
        "_redmi_buds_", // 9 hits
        "_cyberlink_", // 8 hits
        "_wenkia_", // 8 hits
        "_djcontrol_", // 8 hits
        "_device_bluetooth_", // 7 hits
        "_profx_", // 6 hits
        "_avid_mbox_", // 6 hits
        "screencapture_", // 5 hits
        "_wecam_", // 4 hits
        "_decaster_", // 4 hits
        "_winsafe_", // 3 hits
        "_mbrd_", // 3 hits
        "_axis_", // 3 hits
        "_cd04_", // 2 hits
        "_minor_in_", // 2 hits
        "_major_in_", // 2 hits
        "_kontrol_", // 2 hits
        "_niki_nahimic_", // 2 hits
        "_visorgamecapture", // 2 hits
        "_powertoys_", // 2 hits
        "_tims_buds", // 2 hits
        "_subzero_", // 2 hits
        "_unitycam_", // 1 hit
        "_rlurar_haze_", // 1 hit
        "_ngsmedia_", // 1 hit
        "_ngspreview_", // 1 hit
        "_ngscamtocam_", // 1 hit
        "_overlay_", // 1 hit

        "_rodecaster_",
        "_meta_quest_",
        "_iriun_",
        "_boya_",
        "uairplayer",
        "_beats_",
        "_windows_",
        "_z632_",
    ];

    public static function check(array $devices): bool
    {
        $filtered = self::filter($devices);

        return sizeof($filtered) >= 2;
    }

    public static function filter(array $devices): array
    {
        // strip "videoinput_", "audioinput_", etc.
        $devices = self::clean($devices);

        return array_values(array_filter($devices, function ($device) {
            return ! self::has($device);
        }));
    }

    public static function clean(array $devices): array
    {
        // strip "videoinput_", "audioinput_", etc.
        return array_values(array_filter(array_map(function ($device) {
            return preg_replace('/^(video|audio)(in|out)put_?/m', '', $device);
        }, $devices), function ($device) {
            return $device && strlen($device) >= 5 && ! substr($device, 0, 4) !== "gpu_" && ! preg_match('/^_?[\da-f]{4}_[\da-f]{4}_?$/m', $device);
        }));
    }

    public static function has(string $device): ?string
    {
        $device = sprintf('_%s_', $device);

        foreach (self::Wordlist as $word) {
            if (strpos($device, $word) !== false) {
                return $word;
            }
        }

        return null;
    }
}

/*
$test = <<<EOF
EOF;

if (strpos($test, "[") === false) {
    $test = "[" . str_replace('""', '", "', $test) . "]";
}

$devices = json_decode($test, true);

var_dump(DeviceHelper::filter($devices));
//*/
