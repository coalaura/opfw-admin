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
        "_voice_",
        "_device_bluetooth_",
        "_meta_",
        "_casque_",
        "_jabra_",
        "_rode_",
        "_usb_dac_",
        "_soundcard_",
        "_controller_",
        "_fantech_",
        "_spatial_",
        "_nos_",
        "_parleurs_",
        "_avstream_",
        "_cd04_",
        "_decaster_",
        "_nuroum_",
        "_vcam_",
        "_lg_",
        "_wistream_",
        "_motu_",
        "_audient_",
        "_altavoces_",
        "_auriculares_",
        "_eksa_",
        "_djcontrol_",
        "_corsair_",
        "_obsbot_",
        "_streamcamera_",
        "_chromacam_",
        "_xsplit",
        "_scarlett_",
        "_soundbar_",
        "_studio_",
        "_major_in_",
        "_minor_in_",
        "_wenkia_",
        "_kontrol_",
        "_thronmax_",
        "_mbrd_",
        "_k66_",
        "_cyberlink_",
        "_fineshare_",
        "_niki_nahimic_",
        "_intel_",
        "_hodetelefoner_",
        "_visorgamecapture",
        "_gtalare_",
        "_rlurar_haze_",
        "_crusher_",
        "_soundcore_",
        "_conexant_",
        "_easycamera_",
        "_quantum_",
        "_viro_plus_",
        "_samsung_",
        "_avermedia_",
        "_reproduktory_",
        "_nt_usb_",
        "_maono_",
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

if (strpos($test, "[") === false) {
    $test = "[" . str_replace('""', '", "', $test) . "]";
}

$devices = json_decode($test, true);

var_dump(DeviceHelper::filter($devices));
//*/
