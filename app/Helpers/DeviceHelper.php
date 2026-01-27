<?php
namespace App\Helpers;

class DeviceHelper
{
    const Wordlist = [
        "_audio_",
        "_micro",
        "_virtual_",
        "_definition_",
        "_speaker",
        "_nvidia_",
        "_realtek_",
        "_headset_",
        "camera_",
        "_headphones_",
        "_voicemeeter_",
        "_steelseries_",
        "_digital_",
        "_streaming_",
        "_wireless_",
        "_steam_",
        "_sonar_",
        "_output_",
        "_hyperx_",
        "_gaming_",
        "_webcam",
        "_pro_",
        "_angle_",
        "_direct3d11_",
        "_geforce_",
        "_voicemod_",
        "_controller_",
        "_razer_",
        "_arctis_",
        "_earphone_",
        "_line_",
        "_input_",
        "_sound_",
        "_voice_",
        "_astro_",
        "_game_",
        "_quadcast_",
        "_cable_",
        "_logitech_",
        "_broadcast",
        "_stereo_",
        "_yeti_",
        "_lsvcam_",
        "_elgato_",
        "_mikro",
        "_lg_",
        "_corsair_",
        "_helicon_",
        "_goxlr_",
        "_stream_",
        "_mic_",
        "_wave_",
        "_cam_",
        "_stealth_",
        "_intel_",
        "_dell_",
        "_classic_",
        "_capture_",
        "_spatial_",
        "_jbl_",
        "_video_",
        "_interface_",
        "_droidcam_",
        "_display_",
        "_driver_",
        "_acer_",
        "_studio_",
        "_brio_",
        "_lautsprecher_",
        "wball_",
        "_airpods_",
        "_gtalare_",
        "_samsung_",
        "_user_facing_",
        "_mixer_",
        "_e2esoft_",
        "_hdmi_",
        "_monitor_",
        "_source_",
        "_parleurs_",
        "_douwan_",
        "_scarlett_",
        "_obs_camera",
        "_quantum_",
        "_hoofdtelefoon_",
        "_kopfh_rer",
        "_camo_",
        "_maono_",
        "_micr_",
        "_smartcam_",
        "_xsplit",
        "_altavoces_",
        "_prism_",
        "_casque_",
        "_k66_",
        "_jabra_",
        "_plantronics_",
        "_hodetelefoner_",
        "_fantech_",
        "virtualcam",
        "_meta_",
        "_behringer_",
        "studiocam_",
        "_audient_",
        "_crusher_",
        "_reproduktory_",
        "_rode_",
        "_soundbar_",
        "_bose_",
        "_soundcore_",
        "_thronmax_",
        "_auriculares_",
        "_live_streamer_",
        "_avermedia_",
        "_eksa_",
        "_creative_",
        "_earphones_",
        "_obsbot_",
        "_rift_",
        "_conexant_",
        "_communications_",
        "_nos_",
        "_etronvideo_",
        "_loop_back_",
        "_warudocam",
        "_anker_",
        "_beacn_mix_",
        "_soundcard_",
        "_vcam_",
        "_yamaha_",
        "_insta360_",
        "_minifuse_",
        "_nuroum_",
        "_motu_",
        "_fineshare_",
        "_micusb",
        "_viro_plus_",
        "_chromacam_",
        "_rc_505",
        "_katana_",
        "_avstream_",
        "_wistream_",
        "_redmi_buds_",
        "_cyberlink_",
        "_wenkia_",
        "_djcontrol_",
        "_device_bluetooth_",
        "_profx_",
        "_avid_mbox_",
        "screencapture_",
        "_wecam_",
        "_decaster_",
        "_winsafe_",
        "_mbrd_",
        "_axis_",
        "_cd04_",
        "_minor_in_",
        "_major_in_",
        "_kontrol_",
        "_niki_nahimic_",
        "_visorgamecapture",
        "_powertoys_",
        "_tims_buds",
        "_subzero_",
        "_unitycam_",
        "_rlurar_haze_",
        "_ngsmedia_",
        "_ngspreview_",
        "_ngscamtocam_",
        "_overlay_",
        "_rodecaster_",
        "_meta_quest_",
        "_iriun_",
        "_boya_",
        "uairplayer",
        "_beats_",
        "_windows_",
        "_z632_",
        "_htc_vive_",
        "gamecapture",
        "_usb_",
        "_ultraleap_",
        "_universal_",
        "_live_cam",
        "_bigeye_",
        "_dac_amp_",
        "_truevision_",
        "_cybertrack_",
        "facecam_",
        "_rog_delta_",
        "_nova_",
        "_ipcam_",
        "sprekers_",
        "_portable_",
        "_neweye_",
        "_galaxy_buds",
        "_soundgrid_",
        "_smart_tv_",
        "_default_device_",
        "zoomcam_",
        "_mixcast_",
        "_ekacom_",
        "_osmopocket",
        "_airhug_",
        "_ultra_hd_",
        "_xpresscam",
        "_1080p_",
        "_vidbox_",
        "_vision_",
        "_g06_bt_",
        "_tel_fono_",
        "_aula_a_",
        "_localhost_",
        "_none_",
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
            $device = preg_replace('/^(video|audio)(in|out)put_?/m', '', $device);

            return sprintf('_%s_', $device);
        }, $devices), function ($device) {
            return $device && strlen($device) >= 5 && ! substr($device, 0, 4) !== "gpu_" && ! preg_match('/^(_[a-z0-9]{1,4})*_[\da-f]{4}_[\da-f]{4}_$/', $device);
        }));
    }

    public static function has(string $device): ?string
    {
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
