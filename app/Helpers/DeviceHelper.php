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
            return $device && strlen($device) >= 5 && ! substr($device, 0, 4) !== "gpu_";
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

//*
$test = <<<EOF
"audioinput_cable_d_input_voicemod_virtual_audio_device_wdm""audioinput_ndi_webcam_1_newtek_ndi_audio""audioinput_cable_a_output_vb_audio_cable_a""audioinput_microphone_nvidia_broadcast""audioinput_microphone_voice_changer_virtual_audio_device_wdm""audioinput_voicemeeter_out_a1_vb_audio_voicemeeter_vaio""audioinput_microphone_array_altered_virtual_audio_device""audioinput_voicemeeter_out_a5_vb_audio_voicemeeter_vaio""audioinput_microphone_mixline_record""audioinput_microphone_virtual_desktop_audio""audioinput_voicemeeter_out_b3_vb_audio_voicemeeter_vaio""audioinput_voicemeeter_out_b1_vb_audio_voicemeeter_vaio""audioinput_cable_d_output_vb_audio_cable_d""audioinput_ndi_webcam_4_newtek_ndi_audio""audioinput_microphone_mixline_stream""audioinput_cable_b_output_vb_audio_cable_b""audioinput_voicemeeter_out_a3_vb_audio_voicemeeter_vaio""audioinput_voicemeeter_out_b2_vb_audio_voicemeeter_vaio""audioinput_analogue_1_2_2_focusrite_usb_audio""audioinput_voicemeeter_out_a2_vb_audio_voicemeeter_vaio""audioinput_microphone_g435_wireless_gaming_headset_046d_0acb""audioinput_cable_c_input_newtek_ndi_audio""audioinput_voicemeeter_out_a4_vb_audio_voicemeeter_vaio""audioinput_cable_c_input_usb_digital_audio_534d_2109""audioinput_ndi_webcam_3_newtek_ndi_audio""audioinput_cable_c_output_vb_audio_cable_c""audioinput_headset_microphone_oculus_virtual_audio_device""videoinput_animaze_virtual_camera""videoinput_usb_video_534d_2109""videoinput_ndi_webcam_video_1""videoinput_ndi_webcam_video_2""videoinput_ndi_webcam_video_3""videoinput_ndi_webcam_video_4""videoinput_snap_camera""videoinput_joseph_s_s24_ultra_windows_virtual_camera""videoinput_uscreencapture""videoinput_camera_nvidia_broadcast""videoinput_obs_virtual_camera""audiooutput_voicemeeter_input_vb_audio_voicemeeter_vaio""audiooutput_voicemeeter_in_1_vb_audio_voicemeeter_vaio""audiooutput_cable_d_input_realtek_r_audio""audiooutput_voicemeeter_in_3_vb_audio_voicemeeter_vaio""audiooutput_line_voicemod_virtual_audio_device_wdm""audiooutput_cable_d_input_vb_audio_cable_d""audiooutput_voicemeeter_in_2_vb_audio_voicemeeter_vaio""audiooutput_optix_mag271ra_nvidia_high_definition_audio""audiooutput_headset_earphone_g435_wireless_gaming_headset_046d_0acb""audiooutput_cable_b_input_vb_audio_cable_b""audiooutput_voicemeeter_in_5_vb_audio_voicemeeter_vaio""audiooutput_voicemeeter_in_4_vb_audio_voicemeeter_vaio""audiooutput_speakers_thx_spatial_audio""audiooutput_speakers_nvidia_broadcast""audiooutput_m27q_x_nvidia_high_definition_audio""audiooutput_headphones_oculus_virtual_audio_device""audiooutput_g27f_nvidia_high_definition_audio""audiooutput_realtek_hd_audio_2nd_output_realtek_r_audio""audiooutput_speakers_mixline""audiooutput_speakers_2_focusrite_usb_audio""audiooutput_xf273_s_nvidia_high_definition_audio""audiooutput_cable_a_input_vb_audio_cable_a""audiooutput_cable_c_input_vb_audio_cable_c""audiooutput_speakers_realtek_r_audio""audiooutput_speakers_voice_changer_virtual_audio_device_wdm""audiooutput_voicemeeter_vaio3_input_vb_audio_voicemeeter_vaio""audiooutput_voicemeeter_aux_input_vb_audio_voicemeeter_vaio""gpu_angle_nvidia_nvidia_geforce_rtx_4080_direct3d11_vs_5_0_ps_5_0_d3d11""audioinput_headset_2_beats_flex_bluetooth""audiooutput_headphones_2_beats_flex_bluetooth""audioinput_microphone_array_voice_ai_audio_cable""audiooutput_speakers_voice_ai_audio_cable""audiooutput_optix_mag271r_nvidia_high_definition_audio""audioinput_microphone_1080p_webcam_0806_0806""videoinput_1080p_webcam_0806_0806""audioinput_microphone_voicemod_virtual_audio_device_wdm""audioinput_microphone_2_1080p_webcam_0806_0806""audioinput_digital_audio_interface_usb_digital_audio_534d_2109""audioinput_cable_c_input_vb_audio_voicemeeter_vaio""audioinput_cable_b_output_vb_audio_virtual_cable_b""audioinput_analogue_1_2_focusrite_usb_audio""audioinput_cable_c_input_vb_audio_virtual_cable_a""audiooutput_cable_a_input_vb_audio_virtual_cable_a""audiooutput_cable_b_input_vb_audio_virtual_cable_b""audiooutput_cable_a_in_16ch_vb_audio_virtual_cable_a""audiooutput_cable_b_in_16ch_vb_audio_virtual_cable_b""audiooutput_realtek_digital_output_realtek_usb_audio_26ce_0a08""audiooutput_cable_c_input_vb_audio_voicemeeter_vaio""audiooutput_speakers_focusrite_usb_audio""audioinput_microphone_rodecaster_duo_main_stereo_19f7_0050""audioinput_microphone_rodecaster_duo_chat_19f7_0050""videoinput_camera_nvidia_broadcast_windows_virtual_camera""audiooutput_speakers_rodecaster_duo_main_stereo_19f7_0050""audiooutput_speakers_rodecaster_duo_chat_19f7_0050""audioinput_desktop_microphone_3_rodecaster_duo_secondary_19f7_004e""audioinput_chat_input_r_de_unify""audioinput_stream_input_r_de_unify""audiooutput_game_output_r_de_unify""audiooutput_headphones_3_rodecaster_duo_secondary_19f7_004e""audiooutput_chat_output_r_de_unify""audiooutput_system_output_r_de_unify""audiooutput_virtual_output_r_de_unify""audiooutput_browser_output_r_de_unify""audiooutput_music_output_r_de_unify""audioinput_desktop_microphone_4_rodecaster_duo_secondary_19f7_004e""audiooutput_headphones_4_rodecaster_duo_secondary_19f7_004e""audioinput_microphone_steam_streaming_microphone""audiooutput_speakers_steam_streaming_speakers""audiooutput_speakers_steam_streaming_microphone""audioinput_digital_audio_interface_v_z632_345f_2130""videoinput_v_z632_345f_2130""audioinput_mic_in_at_front_panel_pink_realtek_usb_audio_26ce_0a08""audiooutput_headphones_realtek_usb_audio_26ce_0a08"
EOF;

$devices = json_decode("[" . str_replace('""', '", "', $test) . "]", true);

var_dump(DeviceHelper::filter($devices));
//*/
