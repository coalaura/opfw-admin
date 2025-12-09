<template>
    <div>
        <portal to="title">
            <div class="flex justify-between mobile:flex-wrap mt-8 gap-4 w-full">
                <div class="flex gap-4">
                    <!-- Copy License -->
                    <div class="cursor-pointer text-2xl flex items-center" :title="t('players.show.copy_license')" @click="copyLicense">
                        <i class="fas fa-copy relative top-2px"></i>
                    </div>

                    <CountryFlag :country="flagFromTZ(player.variables.tz_name)" :title="player.variables.tz_name + ' - ' + playerTime" class="rounded-sm" v-if="player.variables && player.variables.tz_name" />

                    <h1 class="dark:text-white">
                        {{ player.safePlayerName }}
                    </h1>

                    <div v-if="loadingHWIDLink" class="relative text-pink-600 dark:text-pink-400" :title="t('players.show.loading_extra')">
                        <i class="fas fa-spinner animate-spin absolute top-0"></i>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mobile:flex-wrap mobile:w-full">
                    <!-- Rank -->
                    <badge class="border-purple-200 bg-purple-100 dark:bg-purple-700" :title="t('global.trusted_title')" v-if="player.isTrusted && !player.isStaff">
                        <span class="font-semibold">{{ t('global.trusted') }}</span>
                    </badge>
                    <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale" :title="t('global.staff_title')" v-if="player.isStaff && !player.isSeniorStaff">
                        <span class="font-semibold">{{ t('global.staff') }}</span>
                    </badge>
                    <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale" :title="t('global.senior_staff_title')" v-if="player.isSeniorStaff && !player.isSuperAdmin">
                        <span class="font-semibold">{{ t('global.senior_staff') }}</span>
                    </badge>
                    <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale" :title="t('global.super_title')" v-if="player.isSuperAdmin && !player.isRoot">
                        <span class="font-semibold">{{ t('global.super') }}</span>
                    </badge>
                    <badge class="border-indigo-200 bg-indigo-100 dark:bg-indigo-600" :title="t('global.developer_title')" v-if="player.isRoot">
                        <span class="font-semibold">{{ t('global.developer') }}</span>
                    </badge>

                    <!-- Status & other infos -->
                    <badge class="border-yellow-200 bg-yellow-400 dark:bg-yellow-600" v-if="statusLoading">
                        <span class="font-semibold">{{ t('global.loading') }}</span>
                    </badge>
                    <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale" v-else-if="status && status.character">
                        <span class="font-semibold">{{ t('global.status.online') }}
                            <sup>[{{ status.source }}]</sup>
                        </span>

                        <span class="font-semibold cursor-pointer ml-1" @click="loadStatus()">
                            <i class="fas fa-sync-alt"></i>
                        </span>
                    </badge>
                    <badge class="border-lime-200 bg-lime-100 dark:bg-lime-700" v-else-if="status">
                        <span class="font-semibold">{{ t('global.status.afk') }}
                            <sup>[{{ status.source }}]</sup>
                        </span>

                        <span class="font-semibold cursor-pointer ml-1" @click="loadStatus()">
                            <i class="fas fa-sync-alt"></i>
                        </span>
                    </badge>
                    <badge class="border-red-200 bg-danger-pale dark:bg-dark-danger-pale" v-else>
                        <span class="font-semibold">{{ t('global.status.offline') }}</span>

                        <span class="font-semibold cursor-pointer ml-1" @click="loadStatus()">
                            <i class="fas fa-sync-alt"></i>
                        </span>
                    </badge>

                    <badge class="border-gray-200 bg-secondary dark:bg-dark-secondary" :title="t('players.show.playtime', formatSeconds(player.playTime, 'YMdhm'), formatSeconds(player.recentPlayTime, 'YMdhm'))" v-html="local.played"></badge>

                    <badge class="border-pink-300 bg-pink-200 dark:bg-pink-700" v-if="player.tag" :title="player.tag">
                        <span class="font-semibold">{{ player.tag.length > 20 ? player.tag.substring(0, 18) + '...' : player.tag }}</span>
                    </badge>

                    <badge class="border-red-300 bg-red-200 dark:bg-red-700 cursor-pointer" v-if="globalBans.length > 0 || opfwBanned" :title="opfwBanned ? t('players.show.opfw_banned') : t('players.show.global_title', globalBans.length)" :click="showGlobalBans">
                        <i class="fas fa-skull-crossbones mr-1"></i>
                        <span class="font-semibold">{{ t('players.show.global_info') }}</span>
                    </badge>
                </div>
            </div>
            <div class="text-sm italic mt-4">
                <span class="block" v-if="player.playerName !== player.safePlayerName">
                    <span class="font-bold">{{ t('players.show.original_name') }}: </span>
                    <span class="bg-gray-200 dark:bg-gray-700 px-1 whitespace-pre">{{ highlightUnicode(player.playerName) }}</span>
                </span>
                <span class="block" v-if="player.playerAliases && player.playerAliases.length > 0">
                    <span class="font-bold">{{ t('players.show.aliases') }}:</span>
                    {{ player.playerAliases.join(", ") }}
                </span>
            </div>
            <div class="mt-3 overflow-hidden" :class="{ 'h-4': !showingMoreInfo }">
                <div class="text-sm italic font-semibold">
                    <span @click="showingMoreInfo = !showingMoreInfo" class="cursor-pointer">
                        <span v-if="showingMoreInfo"><i class="fas fa-chevron-down"></i> {{ t('players.show.less_info') }}</span>
                        <span v-else><i class="fas fa-chevron-right"></i> {{ t('players.show.more_info') }}</span>
                    </span>
                </div>
                <table class="whitespace-nowrap !w-auto !m-0">
                    <!-- Last Connection -->
                    <tr class="border-t border-gray-500">
                        <th class="px-2 py-0.5">{{ t('players.show.last_connection') }}</th>
                        <td class="px-2 py-0.5" :title="dayjs.utc(player.lastConnection).fromNow()">{{ player.lastConnection | formatTime(true) }}</td>
                    </tr>

                    <!-- System specs -->
                    <tr class="border-t border-gray-500">
                        <th class="px-2 py-0.5">{{ t('players.show.specs') }}</th>
                        <td class="px-2 py-0.5">
                            <div class="flex gap-2">
                                <span v-if="playerResolution" :title="t('players.show.resolution')">
                                    <i class="fas fa-desktop"></i> {{ playerResolution }}
                                </span>

                                <span :title="t('players.show.average_ping')">
                                    <i class="fas fa-table-tennis"></i> {{ player.averagePing }}ms
                                </span>

                                <span :title="t('players.show.average_fps')">
                                    <i class="fas fa-stopwatch"></i> {{ player.averageFps }}fps
                                </span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </portal>

        <div class="flex flex-wrap justify-between mb-6">
            <div class="mb-3 flex flex-wrap gap-3">
                <!-- Wide Screen -->
                <badge class="border-gray-200 overflow-hidden bg-center bg-cover w-32 cursor-help" style="background-image: url('/images/wide_putin.webp')" v-if="player.stretchedRes && player.stretchedRes.pixelRatio" :title="t('players.show.stretch_res', estimateRatio(player.stretchedRes.aspectRatio), estimateRatio(player.stretchedRes.pixelRatio))"></badge>

                <!-- VPN -->
                <badge class="border-gray-200 overflow-hidden bg-center bg-cover w-16 cursor-help relative" style="background-image: url('/images/vpn.webp')" v-if="isUsingVPN" :title="t('players.show.using_vpn')">
                    <div class="absolute top-0.5 left-px text-[10px] leading-none text-black font-bold">VPN</div>
                </badge>

                <!-- Suspicious Media Devices -->
                <badge class="border-gray-200 overflow-hidden bg-center bg-cover w-16 cursor-help relative" style="background-image: url('/images/sus.webp')" v-if="player.suspicious" :title="t('players.show.suspicious_spoof')">
                    <div class="absolute top-0.5 left-px text-[10px] leading-none text-black font-bold">SUS</div>
                </badge>

                <!-- Debugger -->
                <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale" :title="t('global.debugger_title')" v-if="player.isDebugger && !player.isRoot" square>
                    <i class="fas fa-toolbox"></i>
                </badge>

                <!-- Blacklisted -->
                <badge class="border-red-200 bg-danger-pale dark:bg-dark-danger-pale" :title="t('global.blacklisted')" v-if="blacklisted" square>
                    <i class="fas fa-hand-paper"></i>
                </badge>

                <!-- Whitelisted -->
                <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale" :title="t('global.whitelisted')" v-if="whitelisted" square>
                    <i class="fas fa-clipboard-check"></i>
                </badge>

                <!-- Streamer Ban exception -->
                <a class="font-semibold border-2 px-2 py-1 rounded bg-yellow-100 dark:bg-yellow-700 border-yellow-200 flex items-center gap-1" :href="'https://twitch.tv/' + player.streamerException" target="_blank" v-if="player.streamerException" :title="t('players.show.streamer_exception_title', player.streamerException)">
                    <i class="fas fa-spinner animate-spin mr-1" v-if="updatingBanException"></i>
                    <i class="fab fa-twitch mr-1" v-else></i>
                    {{ t('players.show.streamer_exception') }}

                    <a href="#" @click="removeBanException()" class="ml-1 text-white" :title="t('players.show.remove_ban_exception')" v-if="this.perm.check(this.perm.PERM_BAN_EXCEPTION)">
                        <i class="fas fa-times"></i>
                    </a>
                </a>
            </div>

            <div class="mb-3 flex flex-wrap justify-end gap-3">
                <!-- StaffPM -->
                <button class="px-5 py-2 font-semibold text-white rounded bg-blue-600 dark:bg-blue-500 flex items-center gap-1" @click="isStaffPM = true" v-if="status">
                    <i class="fas fa-envelope-open-text"></i>
                    {{ t('players.show.staffpm') }}
                </button>
                <!-- Kicking -->
                <button class="px-5 py-2 font-semibold text-white rounded bg-yellow-600 dark:bg-yellow-500 flex items-center gap-1" @click="isKicking = true" v-if="status">
                    <i class="fas fa-user-minus"></i>
                    {{ t('players.show.kick') }}
                </button>
                <!-- Edit Ban -->
                <inertia-link class="px-5 py-2 font-semibold text-white rounded bg-yellow-600 dark:bg-yellow-500 flex items-center gap-1" v-bind:href="'/players/' + player.licenseIdentifier + '/bans/' + activeBan.id + '/edit'" v-if="player.isBanned && activeBan.issuer && (!activeBan.locked || this.perm.check(this.perm.PERM_LOCK_BAN))">
                    <i class="fas fa-edit"></i>
                    {{ t('players.show.edit_ban') }}
                </inertia-link>
                <!-- Unmute -->
                <button class="px-5 py-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger flex items-center gap-1" @click="unmutePlayer()" v-if="player.mute">
                    <i class="fas fa-microphone-alt"></i>
                    {{ t('players.show.unmute') }}
                </button>
                <!-- Unbanning -->
                <button class="px-5 py-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger flex items-center gap-1" @click="unbanPlayer()" v-if="player.isBanned && (loadingOpfwBan || !opfwBanned) && (!activeBan.locked || this.perm.check(this.perm.PERM_LOCK_BAN))">
                    <i class="fas fa-lock-open"></i>
                    {{ t('players.show.unban') }}
                </button>
                <!-- Schedule Unban -->
                <button class="px-5 py-2 font-semibold text-white rounded bg-yellow-600 dark:bg-yellow-500 flex items-center gap-1" @click="isSchedulingUnban = true" v-if="player.isBanned && !activeBan.scheduled && player.bans.length === 1 && !(loadingOpfwBan || opfwBanned)">
                    <i class="fas fa-calendar-day"></i>
                    {{ t('players.show.schedule_unban') }}
                </button>
                <!-- Remove Scheduled Unban -->
                <inertia-link class="px-5 py-2 font-semibold text-white rounded bg-purple-600 dark:bg-purple-500 flex items-center gap-1" method="POST" v-bind:href="'/players/' + player.licenseIdentifier + '/bans/' + activeBan.id + '/unschedule'" v-if="player.isBanned && activeBan.scheduled">
                    <i class="fas fa-calendar-times"></i>
                    {{ t('players.show.remove_schedule') }}
                </inertia-link>
                <!-- Banning -->
                <button class="px-5 py-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger flex items-center gap-1" @click="isBanning = true" v-else-if="!player.isBanned">
                    <i class="fas fa-gavel"></i>
                    {{ t('players.show.issue') }}
                </button>
                <!-- Lock ban -->
                <inertia-link class="px-5 py-2 font-semibold text-white rounded bg-purple-600 dark:bg-purple-500 flex items-center gap-1" method="POST" v-bind:href="'/players/' + player.licenseIdentifier + '/bans/' + activeBan.id + '/lock'" v-if="player.isBanned && !activeBan.locked && this.perm.check(this.perm.PERM_LOCK_BAN)">
                    <i class="fas fa-lock"></i>
                    {{ t('players.show.lock_ban') }}
                </inertia-link>
                <!-- Unlock ban -->
                <inertia-link class="px-5 py-2 font-semibold text-white rounded bg-purple-600 dark:bg-purple-500 flex items-center gap-1" method="POST" v-bind:href="'/players/' + player.licenseIdentifier + '/bans/' + activeBan.id + '/unlock'" v-if="player.isBanned && activeBan.locked && this.perm.check(this.perm.PERM_LOCK_BAN)">
                    <i class="fas fa-lock-open"></i>
                    {{ t('players.show.unlock_ban') }}
                </inertia-link>
            </div>

            <!-- Small icon buttons top left -->
            <div class="absolute top-2 left-2 flex gap-2" v-if="this.perm.check(this.perm.PERM_LINKED)">
                <a class="p-1 text-sm font-bold leading-4 text-center rounded border-gray-300 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :href="'/linked_devices/' + player.licenseIdentifier" :title="t('players.show.show_link_devices')" target="_blank" v-if="playerFingerprint">
                    <i class="fas fa-tablet-alt mr-1"></i>
                    Dvc
                </a>
                <a class="p-1 text-sm font-bold leading-4 text-center rounded border-gray-300 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :href="'/linked_tokens/' + player.licenseIdentifier" :title="t('players.show.show_link_token')" target="_blank">
                    <i class="fas fa-drumstick-bite mr-1"></i>
                    Tkn
                </a>
                <a class="p-1 text-sm font-bold leading-4 text-center rounded border-gray-300 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :href="'/linked_ips/' + player.licenseIdentifier" :title="t('players.show.show_link_ip')" target="_blank">
                    <i class="fas fa-ethernet mr-1"></i>
                    IPs
                </a>
                <a class="p-1 text-sm font-bold leading-4 text-center rounded border-gray-300 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :href="'/linked_identifiers/' + player.licenseIdentifier" :title="t('players.show.show_link_identifier')" target="_blank">
                    <i class="fas fa-passport mr-1"></i>
                    Idf
                </a>

                <template v-if="this.$page.auth.player.isSeniorStaff">
                    <div class="w-px bg-white bg-opacity-30 h-full separator">&nbsp;</div>

                    <!-- User Variables -->
                    <button class="p-1 text-sm font-bold leading-4 text-center rounded border-teal-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="showingUserVariables = true" :title="t('players.show.user_variables')">
                        <i class="fas fa-memory mr-1"></i>
                        UVs
                    </button>
                </template>
            </div>

            <!-- Small icon buttons top right -->
            <div class="absolute top-2 right-2 flex gap-2 items-center">
                <!-- Staff statistics -->
                <button v-if="$page.auth.player.isSuperAdmin" class="p-1 text-sm font-bold leading-4 text-center rounded border-teal-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :title="t('players.show.commands_edit')" @click="isEnablingCommands = true; enabledCommands = player.enabledCommands">
                    <i class="fas fa-terminal mr-1"></i>
                    CMD
                </button>

                <button v-if="$page.auth.player.isSeniorStaff && player.isStaff" class="p-1 text-sm font-bold leading-4 text-center rounded border-teal-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :title="t('players.show.staff_stats')" @click="isShowingStaffStatistics = true">
                    <i class="fas fa-heartbeat mr-1"></i>
                    Stats
                </button>

                <div class="w-px bg-white bg-opacity-30 h-full separator" v-if="($page.auth.player.isSeniorStaff && player.isStaff) || $page.auth.player.isSuperAdmin">&nbsp;</div>

                <!-- Damage Logs -->
                <a class="p-1 text-sm font-bold leading-4 text-center rounded border-pink-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :href="'/damage?entity=player&attacker=' + player.licenseIdentifier" :title="t('players.show.damage_logs_by')" v-if="this.perm.check(this.perm.PERM_DAMAGE_LOGS)" target="_blank">
                    <i class="fas fa-hammer mr-1"></i>
                    Out
                </a>
                <!-- Damage Logs 2 -->
                <a class="p-1 text-sm font-bold leading-4 text-center rounded border-pink-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :href="'/damage?victim=' + player.licenseIdentifier" :title="t('players.show.damage_logs')" v-if="this.perm.check(this.perm.PERM_DAMAGE_LOGS)" target="_blank">
                    <i class="fas fa-procedures mr-1"></i>
                    In
                </a>

                <div class="w-px bg-white bg-opacity-30 h-full separator">&nbsp;</div>

                <!-- Edit Streamer exception -->
                <button class="p-1 text-sm font-bold leading-4 text-center rounded border-blue-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="editBanException()" :title="t('players.show.edit_streamer_exception')" v-if="this.perm.check(this.perm.PERM_BAN_EXCEPTION)">
                    <i class="fab fa-twitch mr-1"></i>
                    SEx
                </button>

                <!-- Add Tag -->
                <button class="p-1 text-sm font-bold leading-4 text-center rounded border-green-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="isTagging = true" :title="t('players.show.edit_tag')" v-if="this.perm.check(this.perm.PERM_EDIT_TAG)">
                    <i class="fas fa-tag mr-1"></i>
                    Tag
                </button>

                <!-- Add to Whitelist -->
                <button class="p-1 text-sm font-bold leading-4 text-center rounded border-green-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="updateWhitelistStatus(true)" :title="t('players.show.whitelist')" v-if="!whitelisted && this.perm.check(this.perm.PERM_WHITELIST)">
                    <i class="fas fa-vote-yea mr-1"></i>
                    WL
                </button>

                <!-- Remove from Whitelist -->
                <button class="p-1 text-sm font-bold leading-4 text-center rounded border-red-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="updateWhitelistStatus(false)" :title="t('players.show.unwhitelist')" v-if="whitelisted && this.perm.check(this.perm.PERM_WHITELIST)">
                    <i class="fas fa-calendar-times mr-1"></i>
                    WL
                </button>

                <div class="w-px bg-white bg-opacity-30 h-full separator">&nbsp;</div>

                <!-- Create screen capture -->
                <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-blue-400 bg-secondary dark:bg-dark-secondary border-2 block" @click="isScreenCapture = true; screenCaptureLogs = null" :title="t('screenshot.screencapture')" v-if="status && this.perm.check(this.perm.PERM_SCREENSHOT)">
                    <i class="fas fa-video"></i>
                </button>

                <!-- Create screenshot -->
                <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-blue-400 bg-secondary dark:bg-dark-secondary border-2 block" @click="isScreenshot = true; createScreenshot()" :title="t('screenshot.screenshot')" v-if="status && this.perm.check(this.perm.PERM_SCREENSHOT)">
                    <i class="fas fa-camera"></i>
                </button>

                <div class="w-px bg-white bg-opacity-30 h-full separator">&nbsp;</div>

                <!-- View on Map -->
                <a class="p-1 text-sm font-bold leading-4 text-center rounded border-blue-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :href="'/map#' + status.source" :title="t('global.view_map')" v-if="status && this.perm.check(this.perm.PERM_LIVEMAP)" target="_blank">
                    <i class="fas fa-map mr-1"></i>
                    Map
                </a>

                <!-- Revive -->
                <button class="p-1 text-sm font-bold leading-4 text-center rounded border-yellow-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="revivePlayer()" v-if="status" :title="t('players.show.revive')">
                    <i class="fas fa-heartbeat mr-1"></i>
                    Rez
                </button>
            </div>
        </div>

        <!-- Discord Accounts -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-30" v-if="isShowingDiscord">
            <div class="max-h-max overflow-y-auto shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-4 rounded w-alert">
                <h3 class="mb-2">{{ t('players.show.discord_title') }}</h3>
                <div v-if="isShowingDiscordLoading">
                    <div class="flex justify-center items-center my-6 mt-12">
                        <div>
                            <i class="fas fa-cog animate-spin"></i>
                            {{ t('global.loading') }}
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="w-full flex justify-between" v-for="(discord, id) in discordAccounts" :key="id">
                        <div class="w-full relative">
                            <a class="flex items-center text-lg p-5 m-2 font-semibold text-white bg-discord rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" v-if="discord && discord.username" href="#" :title="t('players.show.discord_copy')" @click="copyText($event, '<@' + discord.id + '> ' + discord.username + (discord.discriminator ? '#' + discord.discriminator : ''))">
                                <img :src="discord.avatar" class="rounded shadow border-2 border-gray-300 w-avatar mr-3" v-handle-error="'/images/discord_failed.png'" />
                                <span>
                                    {{ discord.username }}{{ discord.discriminator ? '#' + discord.discriminator : '' }}
                                </span>
                            </a>
                            <a class="flex items-center text-lg p-5 m-2 font-semibold text-white bg-discord rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" v-else href="#" :title="t('players.show.discord_copy')" @click="copyText($event, '<@' + id + '>')">
                                <i class="mr-1 fab fa-discord"></i>
                                {{ t('players.show.discord', id) }}
                            </a>

                            <i class="fas fa-key absolute top-4 right-4 drop-shadow text-md" v-if="discord && discord.linked" :title="t('players.show.discord_linked')"></i>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end mt-2">
                    <button type="button" class="px-5 py-2 hover:shadow-xl font-semibold text-white rounded bg-dark-secondary mr-3 dark:text-black dark:bg-secondary" @click="isShowingDiscord = false">
                        {{ t('global.close') }}
                    </button>
                </div>
            </div>
        </div>

        <metadataViewer :title="t('players.show.user_variables')" :metadata="player.variables" :show.sync="showingUserVariables"></metadataViewer>

        <!-- System Ban Info -->
        <modal :show.sync="isShowingSystemInfo">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.system_info') }}
                </h1>
            </template>

            <template #default>
                <div class="h-20 flex justify-center items-center" v-if="isSystemInfoLoading">
                    <i class="fas fa-spinner animate-spin mr-1"></i>
                    {{ t('global.loading') }}
                </div>
                <div class="h-20 flex justify-center items-center" v-else-if="!systemInfo">
                    <i class="fas fa-cross mr-1"></i>
                    {{ t('players.show.no_system_info') }}
                </div>
                <div v-else>
                    <p v-html="t('players.show.system_details', systemInfo.type, systemInfo.total, systemInfo.players, systemInfo.average, systemInfo.banned, systemInfo.unbanned, systemInfo.accuracy)" v-if="systemInfo.players > 0"></p>
                    <p v-html="t('players.show.system_no_details', systemInfo.type)" v-else></p>

                    <img :src="systemInfo.graph" class="w-full mt-3" />
                    <caption class="text-sm italic text-left text-gray-600 dark:text-gray-400 block">{{ t('players.show.system_caption') }}</caption>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isShowingSystemInfo = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- Staff Statistics -->
        <modal :show.sync="isShowingStaffStatistics" extraClass="max-w-large">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.staff_stats') }}
                </h1>
            </template>

            <template #default>
                <StatisticsTable source="bans" locale="players.show.source_" :currency="false" :resolve="resolveStaffStatistics" />
                <StatisticsTable source="notes" locale="players.show.source_" :currency="false" :resolve="resolveStaffStatistics" />
                <StatisticsTable source="helpful" locale="players.show.source_" :currency="false" :resolve="resolveStaffStatistics" />

                <div class="border-t-2 border-gray-500 my-6"></div>

                <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4">
                    <h2 class="text-lg mb-1">
                        {{ t("players.show.staff_commands") }}
                    </h2>

                    <input class="block w-full px-4 py-2 bg-gray-200 border rounded dark:bg-gray-600" v-model="statisticsSearch" type="text" placeholder="/revive" />
                </div>

                <StatisticsTable source="armor_self" label="/body_armor (self)" :currency="false" :resolve="resolveStaffStatistics" :search="statisticsSearch" />
                <StatisticsTable source="armor" label="/body_armor (other)" :currency="false" :resolve="resolveStaffStatistics" :search="statisticsSearch" />
                <StatisticsTable source="noclip" label="/noclip" :currency="false" :resolve="resolveStaffStatistics" :search="statisticsSearch" />
                <StatisticsTable source="revive_self" label="/revive (self)" :currency="false" :resolve="resolveStaffStatistics" :search="statisticsSearch" />
                <StatisticsTable source="revive" label="/revive (other)" :currency="false" :resolve="resolveStaffStatistics" :search="statisticsSearch" />
                <StatisticsTable source="spectate" label="/spectate" :currency="false" :resolve="resolveStaffStatistics" :search="statisticsSearch" />
                <StatisticsTable source="staff" label="/staff" :currency="false" :resolve="resolveStaffStatistics" :search="statisticsSearch" />
                <StatisticsTable source="staff_pm" label="/staff_pm" :currency="false" :resolve="resolveStaffStatistics" :search="statisticsSearch" />
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isShowingStaffStatistics = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- Global bans -->
        <modal :show.sync="showingGlobalBans">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.global_info') }}
                </h1>
            </template>

            <template #default>
                <div class="flex flex-col gap-3 text-left" :class="{ 'mb-3 pb-3 border-b-2 border-dashed border-gray-500': globalBans.length }" v-if="opfwBanned">
                    <div class="py-4 px-6 rounded-lg shadow-lg border-2 border-red-500 bg-red-100 dark:bg-red-950 relative">
                        <h1 class="text-lg border-b border-gray-700 dark:border-gray-200">
                            {{ t('players.show.opfw_ban') }}
                        </h1>

                        <div class="text-xs mb-1 flex justify-between opacity-70">
                            <span>{{ opfwBanned.timestamp * 1000 | formatTime }}</span>
                            <span>{{ t('players.show.indefinitely') }}</span>
                        </div>

                        <div class="italic">{{ opfwBanned.reason }}</div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 text-left">
                    <div v-for="ban in globalBans" :key="ban.serverId" class="py-4 px-6 rounded-lg shadow-lg border-2 border-red-500 bg-red-100 dark:bg-red-950">
                        <h1 class="text-lg border-b border-gray-700 dark:border-gray-200 relative">
                            {{ ban.serverName }}
                        </h1>

                        <div class="text-xs mb-1 flex justify-between opacity-70">
                            <span>{{ ban.timestamp * 1000 | formatTime }}</span>

                            <span v-if="ban.expire">{{ ban.expire | humanizeSeconds }}</span>
                            <span v-else>{{ t('players.show.indefinitely') }}</span>
                        </div>

                        <div class="italic">{{ ban.reason }}</div>

                        <div class="text-xs font-semibold absolute bottom-0.5 right-1">{{ ban.serverId }}</div>
                    </div>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingGlobalBans = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- Scheduled Unban -->
        <modal :show.sync="isSchedulingUnban">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.scheduled_unban') }}
                </h1>
            </template>

            <template #default>
                <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" type="date" :min="minDate" v-model="scheduledUnbanDate">

                <p class="italic text-gray-600 dark:text-gray-400 mt-3">{{ t('players.show.schedule_info') }}</p>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isSchedulingUnban = false">
                    {{ t('global.close') }}
                </button>

                <button type="button" class="px-5 py-2 rounded hover:bg-yellow-200 dark:bg-yellow-600 dark:hover:bg-yellow-400" @click="scheduleUnban()">
                    {{ t('players.show.schedule_unban') }}
                </button>
            </template>
        </modal>

        <!-- Linked Accounts -->
        <modal :show.sync="isShowingLinked">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.linked_title') }}
                </h1>
            </template>

            <template #default>
                <div v-if="isShowingLinkedLoading">
                    <div class="flex justify-center items-center my-6 mt-12">
                        <div>
                            <i class="fas fa-cog animate-spin"></i>
                            {{ t('global.loading') }}
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="w-full flex justify-between mb-2" v-for="(link, identifier) in linkedAccounts.linked" :key="identifier">
                        <div class="p-3 w-1/2 relative">
                            <b class="block">{{ link.label }}</b>
                            <pre class="text-xs overflow-hidden overflow-ellipsis" :class="{ 'dark:text-green-300 text-green-700': link.last_used }" :title="identifier">{{ identifier }}</pre>
                        </div>
                        <div class="p-3 w-1/2" v-if="link.accounts.length > 0">
                            <div class="flex mb-2" v-for="account in link.accounts" :key="account.license_identifier">
                                <button @click="unlinkIdentifiers(account.license_identifier)" class="text-white border-2 border-red-200 bg-danger dark:bg-dark-danger font-semibold px-5 py-1 mb-2 mr-2 rounded" v-if="$page.auth.player.isSuperAdmin" :title="t('players.show.unlink')">
                                    <i class="fas fa-unlink"></i>
                                </button>

                                <a class="px-5 py-1 mb-2 border-2 rounded block w-full border-blue-200 bg-primary-pale dark:bg-dark-primary-pale" :href="'/players/' + account.license_identifier" target="_blank">
                                    <span class="font-semibold">{{ account.player_name }}</span>
                                </a>
                            </div>
                        </div>
                        <div class="p-3 w-1/2" v-else>
                            <span class="italic text-sm">{{ t('players.show.no_link') }}</span>
                        </div>
                    </div>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isShowingLinked = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- Notifications -->
        <modal :show.sync="showingNotifications">
            <template #header>
                <h1 class="dark:text-white flex justify-between items-center">
                    {{ t('players.show.notifications') }}

                    <i class="fas fa-plus text-green-600 dark:text-green-400 cursor-pointer text-2xl" v-if="!creatingNotification && !loadingNotifications" @click="creatingNotification = true"></i>
                </h1>
            </template>

            <template #default>
                <div v-if="loadingNotifications">
                    <div class="flex justify-center items-center my-6 mt-12">
                        <div>
                            <i class="fas fa-cog animate-spin"></i>
                            {{ t('global.loading') }}
                        </div>
                    </div>
                </div>

                <div v-else-if="creatingNotification">
                    <textarea class="block bg-gray-200 dark:bg-gray-600 rounded w-full px-4 py-2 h-72" v-model="notification" maxlength="2000"></textarea>
                </div>

                <div v-else>
                    <table class="w-full" v-if="notifications.length > 0">
                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 border-t" :class="{ 'opacity-75': notification.read_at }" v-for="notification in notifications" :key="notification.id">
                            <td class="px-3 py-2">
                                <i class="fas fa-envelope-open-text" :title="t('players.show.read_notification', formatTime(notification.read_at * 1000, true))" v-if="notification.read_at"></i>
                                <i class="fas fa-envelope" :title="t('players.show.not_read_notification')" v-else></i>
                            </td>
                            <td class="px-3 py-2">
                                <a :href="`/players/${notification.creator_identifier}`" target="_blank">{{ notification.player_name }}</a>
                            </td>
                            <td class="px-3 py-2 italic" :title="notification.notification">{{ truncate(notification.notification, 25) }}</td>
                            <td class="px-3 py-2">{{ notification.created_at * 1000 | formatTime(true) }}</td>
                            <td class="px-3 py-2">
                                <i class="fas fa-trash-alt cursor-pointer text-red-800 dark:text-red-400" @click="deleteNotification(notification.id)" v-if="!notification.read_at"></i>
                            </td>
                        </tr>
                    </table>

                    <p v-else>{{ t("players.show.no_notifications") }}</p>
                </div>
            </template>

            <template #actions>
                <template v-if="creatingNotification">
                    <button type="button" class="px-5 py-2 rounded bg-danger dark:bg-dark-danger" @click="creatingNotification = false">
                        {{ t('global.cancel') }}
                    </button>

                    <button type="button" class="px-5 py-2 rounded bg-success dark:bg-dark-success" @click="createNotification()" :disabled="notification.trim().length === 0">
                        {{ t('global.create') }}
                    </button>
                </template>

                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingNotifications = false" v-else>
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- Anti Cheat -->
        <modal :show.sync="isShowingAntiCheat">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.anti_cheat_title') }}
                </h1>
            </template>

            <template #default>
                <div v-if="isShowingAntiCheatLoading">
                    <div class="flex justify-center items-center my-6 mt-12">
                        <div>
                            <i class="fas fa-cog animate-spin"></i>
                            {{ t('global.loading') }}
                        </div>
                    </div>
                </div>
                <div v-else>
                    <table class="w-full" v-if="antiCheatEvents.length > 0">
                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 border-t" v-for="event in antiCheatEvents" :key="event.id">
                            <td class="px-3 py-2">
                                {{ event.type }}

                                <i class="fas fa-images ml-1" v-if="event.screenshot_url" :title="t('players.show.anti_cheat_has_screenshot')"></i>
                            </td>
                            <td class="px-3 py-2">
                                <a href="#" @click="showAntiCheatMetadata($event, event)" class="text-indigo-600 !no-underline dark:text-indigo-300 hover:text-yellow-500 dark:hover:text-yellow-300">
                                    {{ t("players.show.anti_cheat_metadata") }}
                                </a>
                            </td>
                            <td class="px-3 py-2">{{ event.timestamp * 1000 | formatTime(true) }}</td>
                        </tr>
                    </table>
                    <p v-else>{{ t("players.show.no_anti_cheat_events") }}</p>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isShowingAntiCheat = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <metadataViewer :title="t('players.show.anti_cheat_metadata')" :image="antiCheatMetadataImage" :metadata="antiCheatMetadataJSON" :show.sync="antiCheatMetadata"></metadataViewer>

        <!-- Unloading -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-30" v-if="isUnloading">
            <div class="max-h-max overflow-y-auto shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-4 rounded w-alert">
                <h3 class="mb-2">{{ t('players.show.unload') }}</h3>
                <form class="space-y-6" @submit.prevent="unloadCharacter">
                    <!-- Message -->
                    <div class="w-full p-3 flex justify-between">
                        <label class="mr-4 block w-1/4 text-center pt-2 font-bold">
                            {{ t('players.show.unload_msg') }}
                        </label>
                        <textarea class="block bg-gray-200 dark:bg-gray-600 rounded w-3/4 px-4 py-2" id="unload_message" v-model="form.unload.message"></textarea>
                    </div>

                    <p>
                        {{ t('players.show.unload_confirm') }}
                    </p>

                    <!-- Buttons -->
                    <div class="flex items-center space-x-3">
                        <button class="px-5 py-2 font-semibold text-white bg-red-500 rounded hover:bg-red-600" type="submit">
                            <i class="fas fa-bolt mr-1"></i>
                            {{ t('players.show.unload_do') }}
                        </button>
                        <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" type="button" @click="isUnloading = false">
                            {{ t('global.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tag -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-30" v-if="isTagging">
            <div class="max-h-max overflow-y-auto shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-4 rounded w-alert">
                <h3 class="mb-2">{{ t('players.show.edit_tag') }}</h3>
                <form class="space-y-6">
                    <div class="flex">
                        <select class="px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600 w-1/2 mr-1" v-model="tagCategory">
                            <option value="custom">{{ t('players.show.tag_custom') }}</option>
                            <option :value="tag.panel_tag" :key="tag.panel_tag" v-for="tag in tags">{{
                                tag.panel_tag
                            }}
                            </option>
                        </select>

                        <input type="text" class="px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600 w-1/2 ml-1" v-if="tagCategory === 'custom'" v-model="tagCustom" />
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center space-x-3">
                        <button class="px-5 py-2 font-semibold text-white bg-green-500 rounded hover:bg-green-600" type="button" @click="addTag">
                            <i class="fas fa-tag mr-1"></i>
                            {{ t('players.show.edit_tag') }}
                        </button>
                        <button class="px-5 py-2 font-semibold text-white bg-red-500 rounded hover:bg-red-600" type="button" @click="removeTag">
                            {{ t('players.show.remove_tag') }}
                        </button>
                        <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" type="button" @click="isTagging = false">
                            {{ t('global.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Enablable commands -->
        <modal :show.sync="isEnablingCommands">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.enabled_commands') }}
                </h1>
            </template>

            <template #default>
                <MultiSelector :items="enablableKeys" prefix="/" v-model="enabledCommands" />
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-green-100 hover:bg-green-200 dark:bg-green-600 dark:hover:bg-green-400" @click="updateCommands">
                    {{ t('players.show.save_changes') }}
                </button>

                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isEnablingCommands = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- Streamer Exception -->
        <modal :show.sync="editingBanException">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.edit_streamer_exception') }}
                </h1>
            </template>

            <template #default>
                <div class="flex gap-3 items-center">
                    <label class="block whitespace-nowrap" for="banExceptionTwitch">
                        {{ t('players.show.twitch_url') }}
                    </label>

                    <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="banExceptionTwitch" placeholder="https://twitch.tv/..." type="text" v-model="banExceptionTwitch" />
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-green-100 hover:bg-green-200 dark:bg-green-600 dark:hover:bg-green-400" @click="setBanException">
                    <template v-if="updatingBanException">
                        <i class="fas fa-spinner animate-spin mr-1"></i>
                        {{ t("players.show.saving") }}
                    </template>

                    <template v-else>
                        {{ t('players.show.save_changes') }}
                    </template>
                </button>

                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="editingBanException = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- StaffPM -->
        <div>
            <!-- Issuing -->
            <div class="p-8 mb-10 bg-gray-100 rounded dark:bg-dark-secondary" v-if="isStaffPM">
                <div class="mb-8 space-y-5">
                    <h2 class="text-2xl font-semibold">
                        {{ t('players.show.staffpm') }}
                    </h2>
                </div>
                <form class="space-y-6" @submit.prevent="pmPlayer">
                    <!-- Message -->
                    <div>
                        <label class="italic font-semibold" for="pm_message">
                            {{ t('players.show.pm_message') }}
                        </label>
                        <textarea class="block w-full p-5 bg-gray-200 dark:bg-gray-600 rounded shadow" id="pm_message" name="message" rows="5" placeholder="Please join waiting for support" v-model="form.pm.message"></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center space-x-3">
                        <button class="px-5 py-2 font-semibold text-white bg-red-500 rounded hover:bg-red-600" type="submit">
                            <i class="mr-1 fas fa-gavel"></i>
                            {{ t('players.show.pm_confirm') }}
                        </button>
                        <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" type="button" @click="isStaffPM = false">
                            {{ t('global.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kick -->
        <div>
            <!-- Issuing -->
            <div class="p-8 mb-10 bg-gray-100 rounded dark:bg-dark-secondary" v-if="isKicking">
                <div class="mb-8 space-y-5">
                    <h2 class="text-2xl font-semibold">
                        {{ t('players.show.kick') }}
                    </h2>
                </div>
                <form class="space-y-6" @submit.prevent="kickPlayer">
                    <!-- Reason -->
                    <div>
                        <label class="italic font-semibold" for="kick_reason">
                            {{ t('players.show.kick_reason') }}
                        </label>
                        <textarea class="block w-full p-5 bg-gray-200 dark:bg-gray-600 rounded shadow" id="kick_reason" name="reason" rows="5" placeholder="You were kicked from the server." v-model="form.kick.reason"></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center space-x-3">
                        <button class="px-5 py-2 font-semibold text-white bg-red-500 rounded hover:bg-red-600" type="submit">
                            <i class="mr-1 fas fa-gavel"></i>
                            {{ t('players.show.kick') }}
                        </button>
                        <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" type="button" @click="isKicking = false">
                            {{ t('global.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Mute -->
        <alert class="bg-rose-500 dark:bg-rose-500 relative" v-if="player.mute">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-lg font-semibold" v-if="player.mute.expires">
                    {{ t('players.show.muted', formatTime(player.mute.expires * 1000)) }}
                </h2>
                <h2 class="text-lg font-semibold" v-else>
                    {{ t('players.show.muted_forever') }}
                </h2>
                <div class="font-semibold" v-if="player.mute.creator">
                    {{ player.mute.creator }}
                </div>
            </div>

            <p class="text-gray-100">
                <span class="whitespace-pre-line">{{ player.mute.reason || t('players.show.no_reason') }}</span>
            </p>
        </alert>

        <!-- Removing system ban -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-30" v-if="isConfirmingUnban">
            <div class="max-h-max overflow-y-auto shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-4 rounded w-alert">
                <h3 class="mb-2">{{ t('players.show.unban_system_title') }}</h3>
                <div>
                    <p class="select-none">
                        {{ t('players.show.unban_system_confirm') }}
                    </p>

                    <input class="w-full px-4 py-2 !border-red-400 !bg-red-500 !bg-opacity-10 border rounded my-3" v-model="confirmingUnbanInput" placeholder="confirm" :class="{ '!border-lime-400 !bg-lime-500 !bg-opacity-10': confirmingUnbanInput === 'confirm' }" />
                </div>
                <div class="flex justify-end mt-2">
                    <button type="button" class="px-5 py-2 font-semibold text-white rounded bg-dark-secondary dark:text-black dark:bg-secondary" @click="isConfirmingUnban = false">
                        {{ t('global.close') }}
                    </button>
                    <button class="px-5 py-2 rounded bg-danger dark:bg-dark-danger ml-3" type="button" @click="unbanPlayer()" v-if="confirmingUnbanInput === 'confirm'">
                        <i class="mr-1 fas fa-lock-open"></i>
                        {{ t('players.show.unban') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Ban -->
        <div>
            <div class="mb-4 px-6 py-4 border-2 flex flex-col bg-success dark:bg-dark-success rounded border-green-800" v-if="confirmedAccuracy">
                <span class="font-bold">
                    <i class="fas fa-check mr-1"></i>
                    {{ t('players.show.confirmed_accuracy') }}
                </span>
                <span class="text-sm italic">{{ t('players.show.confirmed_accuracy_title') }}</span>
            </div>

            <div class="mb-4 px-6 py-4 border-2 flex flex-col bg-warning dark:bg-dark-warning rounded border-yellow-800" v-else-if="prettyHighAccuracy">
                <span class="font-bold">
                    <i class="fas fa-check mr-1"></i>
                    {{ t('players.show.high_accuracy') }}
                </span>
                <span class="text-sm italic">{{ t('players.show.high_accuracy_title') }}</span>
            </div>

            <div class="mb-4 px-6 py-4 border-2 flex flex-col bg-purple-600 dark:bg-purple-500 rounded border-purple-800" v-if="player.isBanned && activeBan.scheduled">
                <span class="font-bold">
                    <i class="fas fa-calendar-day mr-1"></i>
                    {{ t('players.show.scheduled_unban') }}
                </span>
                <span class="text-sm italic" v-html="t('players.show.scheduled_details', scheduledUnban, scheduledUnbanIn)"></span>
            </div>

            <!-- Viewing -->
            <div class="mb-10">
                <alert class="bg-danger dark:bg-dark-danger px-6 py-4 mb-4 relative" :class="{ 'border-double border-4 border-red-400 !bg-red-800 pt-6': opfwBanned }" v-if="player.isBanned">
                    <a v-if="opfwBanned" class="absolute top-0.5 left-1 text-xxs font-semibold" :href="opfwBanned.appeal" target="_blank" title="OPFW Global Ban appeal form.">
                        {{ t('players.show.global_opfw_ban') }}
                    </a>

                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-lg font-semibold">
                            <i class="fas fa-shield-alt mr-1 cursor-help" v-if="player.streamerException" :title="t('players.show.streamer_exception_title', player.streamerException)"></i>

                            <span v-html="local.ban" :class="{ 'line-through': status && player.streamerException }"></span>
                        </h2>
                        <div class="font-semibold">
                            <i class="mr-1 fas fa-lock" v-if="activeBan.locked" :title="t('players.show.ban_locked')"></i>
                            {{ activeBan.timestamp | formatTime }}
                        </div>
                    </div>

                    <p class="text-gray-100">
                        <span class="whitespace-pre-line">{{ activeBan.reason || t('players.show.no_reason') }}</span>
                    </p>

                    <div class="flex justify-between">
                        <p class="text-sm font-mono">
                            {{ activeBan.banHash }}
                            <template v-if="activeBan.creationReason">/ {{ activeBan.creationReason }}</template>
                        </p>
                        <p class="text-sm font-mono font-semibold" v-if="activeBan.smurfAccount" :title="t('players.show.original_ban')">
                            <a :href="'/smurf/' + activeBan.smurfAccount" target="_blank" class="text-white hover:text-gray-800">{{ activeBan.smurfAccount }}</a>
                        </p>
                    </div>

                    <div class="mt-4 text-sm pt-1 border-t border-dashed" v-if="activeBan.info">
                        <b class="whitespace-nowrap" :class="{ 'cursor-help': isModdingBan() }" @click="showSystemInfo()">{{ activeBan.original }}:</b> <i>{{ activeBan.info }}</i>
                    </div>
                </alert>

                <alert class="bg-orange-500 mb-4" v-if="player.bans.length > 1">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">
                            {{ t('players.show.multiple_bans') }}
                        </h2>
                    </div>

                    <p class="text-gray-100 italic text-xs">
                        {{ t('players.show.multiple_bans_details', player.bans.length) }}
                    </p>

                    <table class="w-full text-sm mt-2">
                        <tr class="border-t border-gray-800 bg-black bg-opacity-10" v-if="activeBan.banHash !== ban.banHash" v-for="ban in player.bans" :key="ban.banHash">
                            <td class="px-2 py-1 font-mono whitespace-nowrap" :title="t('players.show.ban_hash') + (ban.creationReason ? ' / ' + ban.creationReason : '')">
                                {{ ban.banHash }}
                            </td>
                            <td class="px-2 py-1" :title="ban.original">
                                {{ ban.original ? truncate(ban.original, 100) : t('players.show.no_reason') }}
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap" :title="t('players.show.ban_creator')">
                                {{ ban.issuer ? ban.issuer : t('global.system') }}
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap" :title="t('players.show.issue_date')">
                                {{ ban.timestamp | formatTime }}
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap" :title="t('players.show.expiry_date')">
                                <template v-if="ban.expire">
                                    {{ ban.expireAt | formatTime }}
                                </template>
                                <template v-else>
                                    {{ t('players.show.indefinite') }}
                                </template>
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <i class="fas fa-clock ml-1" v-if="ban.expire" :title="t('players.show.timed_ban')"></i>
                                <i class="fas fa-infinity ml-1" v-else :title="t('players.show.indefinite_ban')"></i>

                                <i class="fas fa-lock ml-1" v-if="ban.scheduled" :title="t('players.show.ban_scheduled')"></i>
                                <i class="fas fa-lock ml-1" v-if="ban.locked" :title="t('players.show.ban_locked')"></i>
                            </td>
                        </tr>
                    </table>
                </alert>

                <alert class="bg-rose-500 mb-4" v-if="hwidBan">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">
                            {{ t('players.show.hwid_ban') }}
                        </h2>

                        <div class="flex gap-4">
                            <button @click="unlinkHWID" class="text-rose-700 font-semibold px-3 py-1 rounded bg-white shadow" v-if="$page.auth.player.isSuperAdmin" :title="t('players.show.unlink')">
                                <i class="fas fa-unlink"></i>
                            </button>

                            <inertia-link :href="'/players/' + hwidBan.license" class="text-rose-700 font-semibold px-3 py-1 rounded bg-white shadow">
                                {{ hwidBan.hash }}
                                <i class="fas fa-chevron-right"></i>
                            </inertia-link>
                        </div>
                    </div>
                </alert>
            </div>

            <!-- Issuing -->
            <div class="p-8 mb-10 bg-gray-100 rounded dark:bg-dark-secondary" v-if="isBanning">
                <template v-if="isBanLoading">
                    <img :src="'/images/banned.webp'" class="h-72" style="image-rendering: pixelated" />
                </template>
                <template v-else>
                    <div class="mb-8 space-y-5">
                        <h2 class="text-2xl font-semibold">
                            {{ t('players.ban.issuing') }}
                        </h2>
                        <p class="text-gray-900 dark:text-gray-100" v-html="local.ban_warning"></p>
                    </div>
                    <form class="space-y-6" @submit.prevent="submitBan">
                        <!-- Deciding if ban is temporary -->
                        <div class="flex items-center space-x-3">
                            <input class="block p-3 bg-gray-200 rounded shadow" type="checkbox" id="tempban" name="tempban" v-model="isTempBanning">
                            <label class="italic font-semibold" for="tempban">
                                {{ t('players.ban.temporary') }}
                            </label>
                        </div>

                        <!-- Expiration -->
                        <div class="flex flex-wrap" v-if="isTempBanning">
                            <div class="flex items-center space-x-3 w-full mb-3">
                                <input class="block p-3 bg-gray-200 rounded shadow" type="checkbox" id="tempselect" v-model="isTempSelect">
                                <label class="italic font-semibold" for="tempselect">
                                    {{ t('players.ban.temp-select') }}
                                </label>
                            </div>

                            <div class="w-full" v-if="isTempSelect">
                                <label class="italic font-semibold block mb-1">
                                    {{ t('players.ban.expiration') }}
                                </label>
                                <div class="flex items-center">
                                    <input class="block p-3 bg-gray-200 dark:bg-gray-600 rounded shadow mr-1" type="date" id="expireDate" name="expireDate" step="any" :min="dayjs().format('YYYY-MM-DD')" v-model="form.ban.expireDate" required>
                                    <input class="block p-3 bg-gray-200 dark:bg-gray-600 rounded shadow" type="time" id="expireTime" name="expireTime" step="any" v-model="form.ban.expireTime" required>
                                </div>
                            </div>

                            <div class="mr-1" v-if="!isTempSelect">
                                <label class="italic font-semibold block mb-1">
                                    {{ t('players.ban.temp-value') }}
                                </label>
                                <input class="block p-3 bg-gray-200 dark:bg-gray-600 rounded shadow min-w-input" type="number" min="1" id="ban-value" step="1" required>
                            </div>
                            <div v-if="!isTempSelect">
                                <label class="italic font-semibold block mb-1">
                                    {{ t('players.ban.temp-type') }}
                                </label>
                                <select class="px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600 min-w-input" id="ban-type">
                                    <option value="hour">{{ t('players.ban.hour') }}</option>
                                    <option value="day">{{ t('players.ban.day') }}</option>
                                    <option value="week">{{ t('players.ban.week') }}</option>
                                    <option value="month">{{ t('players.ban.month') }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="italic font-semibold block mb-1" for="reason">
                                {{ t('players.ban.reason') }}
                            </label>
                            <input class="block w-full p-3 px-4 bg-gray-200 dark:bg-gray-600 rounded shadow" id="reason" name="reason" placeholder="1.1, 1.2 | create a ticket" v-model="form.ban.reason" />
                        </div>

                        <!-- Ban Note -->
                        <div>
                            <label class="italic font-semibold block mb-1" for="note">
                                {{ t('players.ban.note') }}
                            </label>
                            <textarea class="block w-full p-3 px-4 bg-gray-200 dark:bg-gray-600 rounded shadow" id="note" name="note" rows="3" :placeholder="player.playerName + ' did a big oopsie.'" v-model="form.ban.note"></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center space-x-3">
                            <button class="px-5 py-2 font-semibold text-white bg-red-500 rounded hover:bg-red-600" type="submit">
                                <i class="mr-1 fas fa-gavel"></i>
                                {{ t('players.ban.do_ban') }}
                            </button>
                            <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" type="button" @click="isBanning = false">
                                {{ t('global.cancel') }}
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- Useful links -->
        <v-section class="dark:bg-dark-secondary" :noFooter="true" :noHeader="true">
            <div class="flex flex-wrap items-center text-center">
                <inertia-link class="flex-1 block p-3 m-2 font-semibold text-white bg-indigo-600 rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" :href="'/logs?identifier=' + player.licenseIdentifier">
                    <i class="mr-1 fas fa-toilet-paper"></i>
                    {{ t('players.show.logs') }}
                </inertia-link>

                <button class="flex-1 block p-3 m-2 font-semibold text-white bg-teal-600 rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" @click="showNotifications">
                    <i class="mr-1 fas fa-envelope-open-text"></i>
                    <span>
                        {{ t('players.show.notifications') }}
                    </span>
                </button>

                <button class="flex-1 block p-3 m-2 font-semibold text-white bg-rose-700 rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" @click="showAntiCheat" v-if="canSeeAntiCheat">
                    <i class="mr-1 fas fa-bullseye"></i>
                    <span>
                        {{ t('players.show.anti_cheat') }}
                    </span>
                </button>
            </div>
            <div class="flex flex-wrap items-center text-center">
                <a class="flex-1 block p-3 m-2 font-semibold text-white bg-discord rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" v-if="player.discord.length > 0" href="#" @click="showDiscord($event)">
                    <i class="mr-1 fab fa-discord"></i>
                    {{ t('players.show.discord_accounts', player.discord.length) }}
                </a>

                <a class="flex-1 block p-3 m-2 font-semibold text-white bg-steam rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" target="_blank" :href="player.steamProfileUrl" v-if="player.steamProfileUrl && !$page.discord.sso">
                    <i class="mr-1 fab fa-steam"></i>
                    {{ t('players.show.steam') }}
                </a>

                <button class="flex-1 block p-3 m-2 font-semibold text-white bg-indigo-600 rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" @click="showLinked">
                    <span>
                        {{ t('players.show.linked') }}
                    </span>
                </button>
            </div>
        </v-section>

        <!-- Characters -->
        <v-section :noFooter="true" :collapsed="charactersCollapsed">
            <template #header>
                <div class="flex justify-between">
                    <div class="flex gap-4">
                        <div class="cursor-pointer text-2xl flex items-center" @click="charactersCollapsed = !charactersCollapsed">
                            <i class="fas fa-chevron-right" :class="{ 'rotate-90': !charactersCollapsed }"></i>
                        </div>

                        <h2>
                            {{ t('players.characters.characters') }}
                        </h2>
                    </div>

                    <button class="block px-5 py-2 font-semibold text-center text-white bg-gray-500 rounded" :class="{ 'bg-blue-500': isShowingDeletedCharacters }" @click="hideDeleted" v-if="!charactersCollapsed && deletedCharacterCount > 0">
                        <span v-if="isShowingDeletedCharacters">
                            {{ t('players.characters.hide') }}
                        </span>
                        <span v-else>
                            {{ t('players.characters.show', deletedCharacterCount) }}
                        </span>
                    </button>
                </div>
            </template>

            <template>
                <div class="grid grid-cols-1 xl:grid-cols-2 3xl:grid-cols-3 wide:grid-cols-4 gap-9 max-h-section overflow-y-auto overflow-x-hidden">
                    <card v-for="(character) in characters" :key="character.id" v-bind:deleted="character.characterDeleted" class="relative mb-0" :class="{ 'shadow-lg': status && status.character === character.id }">
                        <template #header>
                            <div class="flex justify-between gap-3">
                                <div class="flex-shrink-0">
                                    <img class="w-32 h-32 rounded-2xl" :src="'/images/loading.svg'" :data-lazy="character.mugshot" v-handle-error="'/images/no_mugshot.png'" v-if="character.mugshot" />
                                    <img class="w-32 h-32 rounded-2xl" :src="'/images/no_mugshot.png'" v-else :title="t('players.characters.no_mugshot')" />
                                </div>
                                <div class="w-full overflow-hidden">
                                    <h3 class="mb-2 border-b-2 border-dashed border-gray-500">
                                        {{ character.name }}
                                    </h3>

                                    <div class="absolute bottom-1 left-1.5 text-sm font-semibold">#{{ character.id }}</div>
                                    <div class="absolute bottom-1 right-1.5 text-sm font-semibold" v-if="character.bloodType" :title="character.bloodType.info">
                                        {{ character.bloodType.name }}
                                    </div>

                                    <table class="whitespace-nowrap text-sm text-left w-full">
                                        <tr class="border-t border-gray-500">
                                            <th class="px-2 py-0.5 font-semibold">{{ t('players.characters.created_at') }}</th>
                                            <td class="px-2 py-0.5 italic w-full">{{ character.characterCreationTimestamp | formatTime }}</td>
                                        </tr>

                                        <tr class="border-t border-gray-500" v-if="character.characterDeleted">
                                            <th class="px-2 py-0.5 font-semibold">{{ t('players.characters.deleted_at') }}</th>
                                            <td class="px-2 py-0.5 italic w-full">{{ character.characterDeletionTimestamp | formatTime }}</td>
                                        </tr>

                                        <tr class="border-t border-gray-500">
                                            <th class="px-2 py-0.5 font-semibold">{{ t('players.characters.playtime_label') }}</th>
                                            <td class="px-2 py-0.5 italic w-full">
                                                <span :title="formatSeconds(character.playtime, false)">{{ character.playtime | formatSeconds }}</span>
                                                <i class="fas fa-signal ml-1 cursor-help" :title="t('players.characters.playtime_recent', formatSeconds(character.playtime_2w), formatSeconds(character.playtime_4w))"></i>
                                            </td>
                                        </tr>

                                        <tr class="border-t border-gray-500">
                                            <th class="px-2 py-0.5 font-semibold">{{ t('players.characters.born') }}</th>
                                            <td class="px-2 py-0.5 italic w-full">{{ character.dateOfBirth | formatDate }}</td>
                                        </tr>

                                        <tr class="border-t border-gray-500" v-if="character.marriedTo">
                                            <th class="px-2 py-0.5 font-semibold">{{ t('players.characters.married_to') }}</th>
                                            <td class="px-2 py-0.5 italic w-full">
                                                <template v-if="typeof character.marriedTo === 'object'">
                                                    <a :href="'/players/' + character.marriedTo.license + '/characters/' + character.marriedTo.id" class="text-indigo-600 dark:text-indigo-400" target="_blank">
                                                        {{ character.marriedTo.first_name }} {{ character.marriedTo.last_name }}
                                                        #{{ character.marriedTo.id }}
                                                    </a>
                                                </template>

                                                <template v-else>
                                                    #{{ character.marriedTo }}

                                                    <i class="fas fa-user-astronaut ml-1 cursor-pointer" @click="loadMarriedTo(character)" :title="t('players.characters.who_married')"></i>
                                                </template>

                                                <i class="fas fa-ring ml-2 cursor-pointer text-red-600 dark:text-red-400" :title="t('players.characters.divorce')" @click="divorceCharacter(character.id)" v-if="$page.auth.player.isSuperAdmin"></i>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </template>

                        <template>
                            <div class="overflow-y-auto text-sm leading-5 italic">
                                <p class="break-words" v-if="character.backstory.length < 120">
                                    {{ character.backstory }}
                                </p>

                                <p class="break-words" v-else :title="character.backstory">
                                    {{ character.backstory.substr(0, 120) }}...
                                </p>
                            </div>
                        </template>

                        <template #footer>
                            <div class="flex justify-between flex-wrap">
                                <div class="flex justify-between gap-2 w-full">
                                    <inertia-link class="block w-full px-3 py-2 text-center text-white bg-blue-600 dark:bg-blue-400 rounded" :href="'/players/' + (player.overrideLicense ? player.overrideLicense : player.licenseIdentifier) + '/characters/' + character.id">
                                        <i class="fas fa-eye mr-1"></i>
                                        {{ t('global.view') }}
                                    </inertia-link>

                                    <inertia-link class="block w-full px-3 py-2 text-center text-white bg-red-600 dark:bg-red-400 rounded" href="#" @click="deleteCharacter($event, character.id)" v-if="!character.characterDeleted && $page.auth.player.isSuperAdmin">
                                        <i class="fas fa-trash-alt mr-1"></i>
                                        {{ t('players.characters.delete') }}
                                    </inertia-link>
                                </div>

                                <!-- Small icon buttons -->
                                <div class="absolute top-1 left-1 right-1 flex gap-2 justify-between text-white">
                                    <!-- Top left -->
                                    <div class="flex gap-1.5">
                                        <!-- Show inventory -->
                                        <inertia-link class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-blue-300 bg-blue-600 dark:bg-blue-400 border-2 block" :href="'/inventory/character-' + character.id + ':1'" :title="t('inventories.show_inv')">
                                            <i class="fas fa-box"></i>
                                        </inertia-link>

                                        <!-- Show inventory logs -->
                                        <inertia-link class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-blue-300 bg-blue-600 dark:bg-blue-400 border-2 block" :href="'/inventory/logs/character-' + character.id" :title="t('inventories.view_logs')">
                                            <i class="fas fa-suitcase"></i>
                                        </inertia-link>
                                    </div>

                                    <!-- Top right -->
                                    <div class="flex gap-1.5 justify-end">
                                        <!-- Character loaded -->
                                        <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-lime-300 bg-lime-600 dark:bg-lime-400 border-2 block cursor-pointer" :title="t('players.show.unload')" v-if="status && status.character === character.id" @click="form.unload.character = character.id; isUnloading = true">
                                            <i class="fas fa-plug"></i>
                                        </button>

                                        <!-- Character dead -->
                                        <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-red-300 bg-red-600 dark:bg-red-400 border-2 block cursor-help" v-if="character.isDead" @click="reviveCharacter(character.id)" :title="canReviveCharacter(character.id) ? t('players.characters.revive_dead') : ''" :class="{ 'left-10': status && status.character === character.id, '!cursor-pointer': canReviveCharacter(character.id) }">
                                            <i class="fas fa-skull-crossbones"></i>
                                        </button>

                                        <!-- Gender -->
                                        <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-pink-300 bg-pink-600 dark:bg-pink-400 border-2 block" v-if="character.gender === 1" :title="t('players.characters.is_female')">
                                            <i class="fas fa-female"></i>
                                        </button>
                                        <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-blue-300 bg-blue-600 dark:bg-blue-400 border-2 block" v-if="character.gender === 0" :title="t('players.characters.is_male')">
                                            <i class="fas fa-male"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </card>
                </div>
                <p class="text-muted dark:text-dark-muted" v-if="characters.length === 0">
                    {{ t('players.characters.none') }}
                </p>
            </template>
        </v-section>

        <!-- Warnings -->
        <v-section :collapsed="warningsCollapsed">
            <template #header>
                <div class="flex justify-between">
                    <div class="flex gap-4">
                        <div class="cursor-pointer text-2xl flex items-center" @click="warningsCollapsed = !warningsCollapsed">
                            <i class="fas fa-chevron-right" :class="{ 'rotate-90': !warningsCollapsed }"></i>
                        </div>

                        <h2>
                            {{ t('players.show.warnings') }}
                        </h2>
                    </div>

                    <button class="block px-5 py-2 font-semibold text-center text-white bg-gray-500 rounded" :class="{ 'bg-blue-500': showSystemWarnings }" @click="showSystemWarnings = !showSystemWarnings" v-if="!warningsCollapsed">
                        <span v-if="showSystemWarnings">
                            {{ t('players.show.hide_system', systemNoteCount) }}
                        </span>
                        <span v-else>
                            {{ t('players.show.show_system', systemNoteCount) }}
                        </span>
                    </button>
                </div>
            </template>

            <template>
                <template v-for="(warning, index) in warnings">
                    <template v-if="isAutomatedWarning(warning)">
                        <div v-if="showSystemWarnings" class="flex flex-col px-8 mb-5 bg-white dark:bg-gray-600 rounded-lg shadow-sm relative opacity-50 hover:opacity-100" :class="{ '!opacity-100': selectedWarnings.includes(warning.id), 'mb-3': index + 1 < warnings.length && isAutomatedWarning(warnings[index + 1]) }">
                            <header class="text-center">
                                <div class="flex justify-between gap-4">
                                    <div class="flex justify-between gap-4">
                                        <div class="flex items-center py-4 pr-4 border-r border-gray-200 dark:border-gray-400 w-32 flex-shrink-0">
                                            <h4 class="truncate" v-if="warning.issuer.playerName === null">{{ t('global.system') }}</h4>
                                            <h4 class="truncate" :title="warning.issuer.playerName">
                                                <a :href="'/players/' + warning.issuer.licenseIdentifier">{{ warning.issuer.playerName }}</a>
                                            </h4>
                                        </div>

                                        <div class="flex items-center py-4">
                                            <span class="italic text-xs text-muted dark:text-dark-muted text-left" v-html="formatWarning(warning.message)"></span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end py-4 pl-4 border-l border-gray-200 dark:border-gray-400 gap-3">
                                        <span class="italic text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap block w-36 text-right">
                                            {{ warning.createdAt | formatTime }}
                                        </span>

                                        <button class="block px-2 py-1 text-sm font-semibold text-white bg-gray-500 border-2 border-gray-500 rounded" :class="{ '!bg-red-500 hover:!bg-red-600 !border-red-900': selectedWarnings.includes(warning.id) }" @click="selectWarning(warning.id)" v-if="warning.canDelete || $page.auth.player.isSeniorStaff">
                                            <i class="fas fa-recycle"></i>
                                        </button>

                                        <button class="block px-2 py-1 text-sm font-semibold text-white bg-red-500 rounded hover:bg-red-600" @click="deleteWarning(warning.id)" v-bind:href="'/players/' + player.licenseIdentifier + '/warnings/' + warning.id" v-if="warning.canDelete || $page.auth.player.isSeniorStaff">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </header>
                        </div>
                    </template>

                    <card class="relative" :no_footer="true" v-else>
                        <template #header>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <h4>
                                        <span v-html="getWarningTypeIcon(warning.warningType)"></span>
                                        -
                                        <span v-if="!warning.issuer.licenseIdentifier">{{ t('global.system') }}</span>
                                        <a :href="'/players/' + warning.issuer.licenseIdentifier" v-else>{{ warning.issuer.playerName }}</a>
                                    </h4>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-muted dark:text-dark-muted">
                                        {{ warning.createdAt | formatTime }}
                                    </span>
                                    <sup class="ml-2 italic text-sm text-gray-600 dark:text-gray-400" v-if="warning.updatedAt !== warning.createdAt" :title="t('players.show.warning_edited_title', formatTime(warning.updatedAt))">
                                        {{ t('players.show.warning_edited') }}
                                    </sup>

                                    <div class="ml-3 flex gap-2">
                                        <button class="px-2 py-1 text-sm font-semibold text-white bg-yellow-500 rounded" @click="warningEditId = warning.id" v-if="warningEditId !== warning.id && $page.auth.player.licenseIdentifier === warning.issuer.licenseIdentifier && warning.warningType !== 'system'">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="px-2 py-1 text-sm font-semibold text-white bg-success dark:bg-dark-success rounded" @click="editWarning(warning.id, warning.warningType)" v-if="warningEditId === warning.id">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button class="px-2 py-1 text-sm font-semibold text-white bg-muted dark:bg-dark-muted rounded" @click="warningEditId = 0" v-if="warningEditId === warning.id">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        <button class="px-2 py-1 text-sm font-semibold text-white bg-lime-600 rounded" @click="refreshWarning(warning.id)" v-if="!warningEditId && $page.auth.player.isRoot && $page.auth.player.licenseIdentifier !== warning.issuer.licenseIdentifier">
                                            <i class="fas fa-spinner animate-spin" v-if="refreshingWarning === warning.id"></i>
                                            <i class="fas fa-retweet" v-else></i>
                                        </button>
                                        <button class="block px-2 py-1 text-sm font-semibold text-white bg-gray-500 border-2 border-gray-500 rounded" :class="{ '!bg-red-500 hover:!bg-red-600 !border-red-900': selectedWarnings.includes(warning.id) }" @click="selectWarning(warning.id)" v-if="$page.auth.player.isSeniorStaff">
                                            <i class="fas fa-folder-minus"></i>
                                        </button>
                                        <inertia-link class="px-2 py-1 text-sm font-semibold text-white bg-red-500 rounded hover:bg-red-600" method="DELETE" v-bind:href="'/players/' + player.licenseIdentifier + '/warnings/' + warning.id" v-if="$page.auth.player.isSeniorStaff">
                                            <i class="fas fa-trash-alt"></i>
                                        </inertia-link>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template>
                            <p class="text-muted dark:text-dark-muted max-h-96 overflow-auto" v-if="warningEditId !== warning.id">
                                <span class="whitespace-pre-wrap" v-html="markdown(formatWarning(warning.message), false)"></span>
                            </p>

                            <textarea class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-700" rows="8" :id="'warning_' + warning.id" v-else-if="warningEditId === warning.id">{{ warning.message }}</textarea>

                            <div class="absolute -bottom-2 left-2 flex gap-1.5">
                                <div class="group flex gap-1.5 items-center rounded-md bg-gray-800 border border-gray-800 overflow-hidden p-1 cursor-pointer" @mouseenter="randomizeReaction(warning)" v-if="Object.values(warning.reactions.all).length !== reactions.length">
                                    <i class="fas fa-ellipsis-h text-gray-400 w-4 h-4 block group-hover:hidden" :class="{ '!block': isReacting[warning.id] }"></i>

                                    <div class="gap-2 hidden group-hover:flex">
                                        <img v-for="emoji in reactions" v-if="!warning.reactions.all[emoji]" :src="'/images/reactions/' + emoji + '.' + (animated.includes(emoji) ? 'gif' : 'png')" :title="emoji" class="w-4 h-4 object-cover cursor-pointer saturate-0 hover:saturate-100 hover:brightness-105" :class="{ '!hidden': isReacting[warning.id] }" @click="toggleReaction(warning, emoji)" />
                                    </div>
                                </div>

                                <div class="relative group flex gap-1.5 items-center rounded-md bg-gray-800 border border-gray-800 p-1 cursor-pointer transition-colors hover:!bg-gray-700 hover:!border-gray-600" :class="{ '!bg-gray-500 !border-gray-400': warning.reactions.mine.includes(emoji) }" v-for="emoji in reactions" @mouseenter="hoveringReaction(warning, emoji)" @click="toggleReaction(warning, emoji)" v-if="warning.reactions.all[emoji]">
                                    <img :src="'/images/reactions/' + emoji + '.' + (animated.includes(emoji) ? 'gif' : 'png')" class="w-4 h-4 object-cover" />
                                    <span class="text-xs font-semibold text-gray-400" :class="{ '!text-gray-200': warning.reactions.mine.includes(emoji) }">{{ warning.reactions.all[emoji] }}</span>

                                    <div class="absolute bottom-full -left-4 -translate-y-2 hidden group-hover:flex gap-3 text-sm items-center w-max text-gray-800 bg-gray-200 dark:text-gray-200 dark:bg-gray-800 p-2 rounded whitespace-nowrap" v-if="'hover' in warning && warning.hover && warning.hover[emoji]">
                                        <img :src="'/images/reactions/' + emoji + '.' + (animated.includes(emoji) ? 'gif' : 'png')" class="w-12 h-12 object-cover" />
                                        <div>:{{ emoji }}: {{ t('players.show.reacted_by', smartJoin(warning.hover[emoji])) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute -bottom-2 right-2 flex gap-1.5">
                                <div class="group flex gap-1.5 items-center justify-center rounded-md bg-gray-800 border border-gray-800 overflow-hidden p-1 cursor-pointer" @click="copyWarning(warning)" :title="t('players.show.copy_warning')">
                                    <i class="fas fa-copy text-gray-400 w-4 block text-center group-hover:text-gray-200 transition"></i>
                                </div>
                            </div>
                        </template>
                    </card>
                </template>

                <p class="text-muted dark:text-dark-muted" v-if="warnings.length === 0">
                    {{ t('players.show.no_warnings') }}
                </p>

                <button class="px-5 py-2 rounded font-semibold text-white bg-red-500 hover:bg-red-600 border-2 border-red-900 w-full" v-if="selectedWarnings.length > 0" @click="deleteSelectedWarnings()">
                    {{ t('players.show.delete_selected', selectedWarnings.length) }}
                </button>
            </template>

            <template #footer>
                <h3>
                    {{ t('players.warning.give') }}
                </h3>
                <form @submit.prevent="submitWarning">
                    <label for="message" class="italic text-gray-800 dark:text-gray-200 block text-sm" v-html="t('players.warning.remember')"></label>

                    <div class="mt-3 mb-5 bg-gray-200 dark:bg-gray-600 shadow rounded px-5 py-4">
                        <div class="flex gap-3 items-center" v-if="!form.warning.warning_type">
                            <span class="font-semibold">{{ t('players.warning.create_new') }}</span>

                            <div class="flex gap-2">
                                <button class="px-2.5 py-0.5 text-white bg-red-400 dark:bg-red-500 rounded hover:!bg-red-600" @click="form.warning.warning_type = 'strike'">
                                    <i class="fas fa-bolt mr-0.5"></i>
                                    {{ t('players.warning.strike') }}
                                </button>
                                <button class="px-2.5 py-0.5 text-white bg-orange-400 dark:bg-orange-500 rounded hover:!bg-orange-600" @click="form.warning.warning_type = 'warning'">
                                    <i class="fas fa-exclamation-triangle mr-0.5"></i>
                                    {{ t('players.warning.warning') }}
                                </button>
                                <button class="px-2.5 py-0.5 text-white bg-yellow-400 dark:bg-yellow-500 rounded hover:!bg-yellow-600" @click="form.warning.warning_type = 'note'">
                                    <i class="fas fa-sticky-note mr-0.5"></i>
                                    {{ t('players.warning.note') }}
                                </button>
                                <button class="px-2.5 py-0.5 text-white bg-pink-400 dark:bg-pink-500 rounded hover:!bg-pink-600" @click="form.warning.warning_type = 'hidden'" v-if="$page.auth.player.isSeniorStaff">
                                    <i class="fas fa-eye-slash mr-0.5"></i>
                                    {{ t('players.warning.hidden') }}
                                </button>
                            </div>
                        </div>

                        <div v-else>
                            <div class="mb-3 flex justify-between items-center">
                                <h4 class="text-lg font-semibold">
                                    {{ t('players.warning.new', t('players.warning.' + form.warning.warning_type)) }}
                                </h4>

                                <div class="flex gap-3">
                                    <button class="text-black dark:text-white shadow-sm text-base" @click="form.warning.warning_type = ''; form.warning.message = ''; warningMessageChanged()">
                                        <i class="fas fa-backspace"></i>
                                    </button>

                                    <button class="text-black dark:text-white shadow-sm text-base" type="submit">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="relative">
                                <inertia-link class="text-black dark:text-white no-underline absolute top-0.5 right-1.5" :title="t('global.support_markdown')" href="/docs/markdown">
                                    <i class="fab fa-markdown"></i>
                                </inertia-link>

                                <textarea class="w-full p-5 rounded bg-gray-200 dark:bg-gray-600" id="message" name="message" rows="4" :placeholder="t('players.warning.placeholder', player.playerName)" v-model="form.warning.message" @input="warningMessageChanged()" required></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </template>
        </v-section>

        <!-- Screenshot -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-2k" v-if="isScreenshot && this.perm.check(this.perm.PERM_SCREENSHOT)">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-6 rounded" :class="continuouslyScreenshotting ? 'w-vlarge-alert' : 'w-alert'">
                <h3 class="mb-2">
                    {{ t('map.screenshot') }}
                    <span v-if="nextContinuousScreenshot > 0 && continuouslyScreenshotting"> - {{ nextContinuousScreenshot.toFixed(1) }}s</span>
                </h3>

                <p v-if="screenshotError" class="text-danger dark:text-dark-danger font-semibold mb-3">
                    {{ screenshotError }}
                </p>

                <div class="relative min-h-50">
                    <a v-if="screenshotImage && !screenshotError" class="w-full" :class="{ 'blur-sm': isScreenshotLoading && !continuouslyScreenshotting }" :href="screenshotImage" target="_blank">
                        <img :src="screenshotImage" alt="Screenshot" class="w-full" v-handle-error />
                    </a>

                    <div class="flex justify-center absolute left-0 w-full top-1/2 transform -translate-y-1/2" v-if="isScreenshotLoading && !continuouslyScreenshotting">
                        <i class="fas fa-cog animate-spin text-3xl"></i>
                    </div>
                </div>

                <p v-if="screenshotImage" class="mt-3 text-sm">
                    {{ t('map.screenshot_description') }}
                </p>

                <div v-if="screenshotFlags" class="mb-3">
                    <h4 class="text-base mb-1 mt-2 pt-2 border-t border-gray-500">{{ t('screenshot.flags') }}</h4>

                    <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm" v-html="screenshotFlags.join(', ')"></pre>
                </div>

                <div v-if="screenCaptureLogs" class="mb-5">
                    <h4 class="text-base mb-1 mt-2 pt-2 border-t border-gray-500">{{ t('screenshot.logs') }}</h4>

                    <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm" v-html="screenCaptureLogs.join('\n')"></pre>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end mt-2">
                    <button class="px-5 py-2 rounded bg-primary dark:bg-dark-primary mr-2" @click="startContinuousScreenshot()" v-if="!continuouslyScreenshotting && !isScreenshotLoading">
                        {{ t('screenshot.continuous') }}
                    </button>
                    <button class="px-5 py-2 rounded bg-danger dark:bg-dark-danger mr-2" @click="stopContinuousScreenshot()" v-else-if="continuouslyScreenshotting">
                        <i class="fas fa-cog animate-spin mr-1" v-if="isScreenshotLoading"></i>

                        {{ t('screenshot.continuous_stop') }}
                    </button>

                    <button class="px-5 py-2 rounded bg-success dark:bg-dark-success mr-2" @click="createScreenshot()" v-if="!isScreenshotLoading && !continuouslyScreenshotting">
                        {{ t('global.refresh') }}
                    </button>
                    <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" v-if="!continuouslyScreenshotting" @click="isScreenshot = false; screenshotImage = null; screenshotError = null; screenshotLicense = null">
                        {{ t('global.close') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Screen capture -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-2k" v-if="isScreenCapture && this.perm.check(this.perm.PERM_SCREENSHOT)">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-6 rounded" :class="screenCaptureVideo ? 'w-large-alert' : 'w-alert'">
                <h3 class="mb-2">
                    {{ t('screenshot.screencapture') }}
                </h3>

                <!-- Duration -->
                <div class="w-full p-3 flex justify-between px-0" v-if="!screenCaptureStatus && !screenCaptureVideo">
                    <label class="mr-4 block w-1/4 pt-2 font-bold" for="capture_duration">
                        {{ t('screenshot.capture_duration') }}
                    </label>
                    <input class="w-3/4 px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="capture_duration" min="1" max="30" type="number" v-model="captureData.duration" />
                </div>

                <p v-if="screenCaptureError" class="text-danger dark:text-dark-danger font-semibold mb-3">
                    {{ screenCaptureError }}
                </p>

                <div class="relative min-h-50">
                    <video class="w-full" controls v-if="screenCaptureVideo">
                        <source :src="screenCaptureVideo" type="video/webm">
                    </video>

                    <div class="w-full" v-if="screenCaptureStatus === 'capturing'">
                        <span class="text-sm block mb-1">{{ t('screenshot.capturing', Math.ceil(captureRemaining / 10)) }}</span>
                        <div class="bg-green-700 dark:bg-green-400" :style="`height: 4px; width: ${(1 - (captureRemaining / (captureData.duration * 10))) * 100}%`"></div>
                    </div>

                    <div class="flex justify-center absolute left-0 w-full top-1/2 transform -translate-y-1/2 flex-wrap" v-if="screenCaptureStatus === 'processing'">
                        <i class="fas fa-cog animate-spin text-3xl"></i>
                        <span class="text-sm block mt-1 text-center w-full">{{ t('screenshot.processing') }}</span>
                    </div>
                </div>

                <p v-if="screenCaptureStatus === 'processing'" class="mt-3 text-sm">
                    {{ t('screenshot.processing_description') }}
                </p>

                <p v-if="screenCaptureVideo" class="mt-3 text-sm">
                    {{ t('map.screecapture_description') }}
                </p>

                <div v-if="screenCaptureLogs" class="mb-5">
                    <h4 class="text-base mb-1 mt-2 pt-2 border-t border-gray-500">{{ t('screenshot.logs') }}</h4>

                    <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm" v-html="screenCaptureLogs.join('\n')"></pre>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end mt-2">
                    <a v-if="screenCaptureVideo" :href="screenCaptureVideo" target="_blank" class="px-5 py-2 rounded bg-primary dark:bg-dark-primary mr-2">
                        {{ t('global.download') }}
                    </a>
                    <button class="px-5 py-2 rounded bg-primary dark:bg-dark-primary mr-2" @click="createScreenCapture()" v-if="!screenCaptureStatus && !screenCaptureVideo">
                        {{ t('global.create') }}
                    </button>
                    <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" v-if="!screenCaptureStatus" @click="isScreenCapture = false; captureData.duration = 5; screenCaptureVideo = false; screenCaptureError = false">
                        {{ t('global.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Badge from './../../Components/Badge.vue';
import Alert from './../../Components/Alert.vue';
import Card from './../../Components/Card.vue';
import Avatar from './../../Components/Avatar.vue';
import Modal from './../../Components/Modal.vue';
import MetadataViewer from './../../Components/MetadataViewer.vue';
import StatisticsTable from './../../Components/StatisticsTable.vue';
import MultiSelector from './../../Components/MultiSelector.vue';

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
        Alert,
        Card,
        Avatar,
        Modal,
        MetadataViewer,
        StatisticsTable,
        MultiSelector,
        CountryFlag: () => import('vue-country-flag')
    },
    props: {
        player: {
            type: Object,
            required: true,
        },
        characters: {
            type: Array,
            required: true,
        },
        warnings: {
            type: Array,
            required: true,
        },
        reactions: {
            type: Array,
            required: true,
        },
        animated: {
            type: Array,
            required: true,
        },
        tags: {
            type: Array,
            required: true,
        },
        enablable: {
            type: Array,
            required: true,
        },
        kickReason: {
            type: String
        },
        whitelisted: {
            type: Boolean
        },
        blacklisted: {
            type: Boolean
        }
    },
    data() {
        const autoExpandCollapsed = this.setting('expandCollapsed');
        const showSystemNotes = this.setting('showSystemNotes');

        return {
            local: {
                played: this.player.playTime > 0 ? this.t('players.show.played', this.$options.filters.humanizeSeconds(this.player.playTime)) : this.t('players.show.no_playtime'),
                ban: this.localizeBan(),
                ban_warning: this.t('players.ban.ban_warning')
            },
            isKicking: false,
            isStaffPM: false,
            isTempBanning: false,
            isTempSelect: true,
            warningEditId: 0,
            refreshingWarning: false,
            form: {
                ban: {
                    note: null,
                    reason: null,
                    expire: null,
                    expireDate: null,
                    expireTime: null,
                },
                kick: {
                    reason: null,
                },
                pm: {
                    message: null,
                },
                warning: {
                    message: null,
                    warning_type: null,
                },
                unload: {
                    message: null,
                    character: null
                }
            },
            isShowingDeletedCharacters: false,
            isUnloading: false,
            showSystemWarnings: showSystemNotes,

            isShowingLinked: false,
            isShowingLinkedLoading: false,
            linkedAccounts: {
                total: 0,
                linked: []
            },

            isBanning: false,
            isBanLoading: false,

            isSchedulingUnban: false,
            scheduledUnbanDate: false,

            isEnablingCommands: false,
            enabledCommands: [],

            isShowingDiscord: false,
            isShowingDiscordLoading: false,
            discordAccounts: [],

            isShowingAntiCheat: false,
            isShowingAntiCheatLoading: false,
            antiCheatEvents: [],

            antiCheatMetadata: false,
            antiCheatMetadataJSON: null,
            antiCheatMetadataImage: false,

            isTagging: false,
            tagCategory: this.player.tag ? this.player.tag : 'custom',
            tagCustom: '',

            isConfirmingUnban: false,
            confirmingUnbanInput: "",

            isScreenCapture: false,
            screenCaptureStatus: false,
            screenCaptureVideo: false,
            screenCaptureError: false,
            captureRemaining: false,
            captureData: {
                duration: 5
            },

            nextContinuousScreenshot: 0,
            continuousScreenshotThread: false,
            continuouslyScreenshotting: false,

            isScreenshot: false,
            isScreenshotLoading: false,
            screenshotImage: null,
            screenshotLicense: null,
            screenshotFlags: null,
            screenshotError: null,

            screenCaptureLogs: null,

            deletingWarnings: false,
            selectedWarnings: [],

            loadingHWIDLink: false,

            updatingBanException: false,
            editingBanException: false,
            banExceptionTwitch: "",

            statusLoading: true,
            status: false,

            globalBans: [],
            showingGlobalBans: false,

            loadingOpfwBan: true,
            opfwBanned: false,

            playerTime: false,

            isShowingSystemInfo: false,
            isSystemInfoLoading: false,
            systemInfo: false,

            charactersCollapsed: false,
            warningsCollapsed: true && !autoExpandCollapsed,

            hwidBan: null,

            isUsingVPN: false,

            isLoading: false,
            showingMoreInfo: false,

            showingUserVariables: false,

            isReacting: {},

            isShowingStaffStatistics: false,
            statisticsSearch: '',
            staffStatistics: {},

            showingNotifications: false,
            loadingNotifications: false,
            creatingNotification: false,
            notifications: [],
            notification: ""
        }
    },
    computed: {
        activeBan() {
            if (!this.player.bans.length) return false;

            return this.player.bans[0];
        },
        confirmedAccuracy() {
            const ban = this.activeBan;

            if (!ban?.info) return false;

            return ban.info?.startsWith('Impossible');
        },
        prettyHighAccuracy() {
            const ban = this.activeBan;

            if (!ban?.info) return false;

            return ban.info?.startsWith('Highly unlikely');
        },
        minDate() {
            const date = new Date();

            date.setDate(date.getDate() + 1);

            return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
        },
        scheduledUnban() {
            if (!this.activeBan || !this.activeBan.scheduled) {
                return false;
            }

            return dayjs.utc(this.activeBan.scheduled * 1000).format('MM/DD/YYYY - H:mm A');
        },
        scheduledUnbanIn() {
            if (!this.activeBan || !this.activeBan.scheduled) {
                return false;
            }

            return dayjs.utc(this.activeBan.scheduled * 1000).fromNow();
        },
        systemNoteCount() {
            return this.warnings.filter(warn => this.isAutomatedWarning(warn)).length;
        },
        deletedCharacterCount() {
            return this.characters.filter(c => c.characterDeleted).length;
        },
        playerTimezone() {
            if (!this.player.variables || !this.player.variables.tz_name || typeof this.player.variables.tz_offset !== 'number') return false;

            return this.player.variables.tz_name;
        },
        playerResolution() {
            if (!this.player.variables || !this.player.variables.screen_width || !this.player.variables.screen_height) return false;

            return `${this.player.variables.screen_width}x${this.player.variables.screen_height}`;
        },
        playerFingerprint() {
            if (!this.player.variables || !this.player.variables.fingerprint) return false;

            return this.player.variables.fingerprint;
        },
        enablableKeys() {
            return Object.keys(this.enablable).toSorted();
        },
    },
    methods: {
        isModdingBan() {
            return this.activeBan.original?.startsWith('MODDING');
        },
        async loadMarriedTo(character) {
            const marriedTo = character.marriedTo;

            if (!marriedTo || !Number.isInteger(marriedTo)) return;

            try {
                const response = await _get(`/api/character/${marriedTo}`);

                if (response?.status) {
                    character.marriedTo = response.data;
                }
            } catch (e) { }
        },
        async divorceCharacter(characterId) {
            if (this.isLoading || !confirm(this.t('players.characters.divorce_confirm'))) {
                return;
            }

            this.isLoading = true;

            // Send request.
            await this.$inertia.delete(`/players/${this.player.licenseIdentifier}/characters/${characterId}/divorce`);

            this.isLoading = false;
        },
        canReviveCharacter(characterId) {
            if (!this.$page.auth.player.isSuperAdmin) {
                return false;
            }

            if (this.status && this.status.character === characterId) {
                return false;
            }

            return true;
        },
        async reviveCharacter(characterId) {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/characters/${characterId}/revive_offline`);

            this.isLoading = false;
        },
        async showSystemInfo() {
            if (this.isSystemInfoLoading || !this.isModdingBan()) {
                return;
            }

            this.isShowingSystemInfo = true;

            this.isSystemInfoLoading = true;
            this.systemInfo = false;

            try {
                const response = await _get(`/players/${this.player.licenseIdentifier}/bans/${this.activeBan.id}/system`);

                if (response?.status) {
                    this.systemInfo = response.data;
                }
            } catch {
            }

            this.isSystemInfoLoading = false;
        },
        async showNotifications() {
            if (this.loadingNotifications) {
                return;
            }

            this.showingNotifications = true;
            this.creatingNotification = false;
            this.loadingNotifications = true;

            this.notifications = [];

            try {
                const response = await _get(`/players/${this.player.licenseIdentifier}/notifications`);

                if (response?.status) {
                    this.notifications = response.data;
                }
            } catch {}

            this.loadingNotifications = false;
        },
        async createNotification() {
            if (this.loadingNotifications) {
                return;
            }

            const text = this.notification.trim();

            if (!text.length) {
                return;
            }

            this.loadingNotifications = true;
            this.creatingNotification = false;

            try {
                const response = await _post(`/players/${this.player.licenseIdentifier}/notifications`, {
                    notification: text,
                });

                if (response?.status) {
                    this.notification = "";

                    this.notifications = response.data;
                } else {
                    alert(response?.error || this.t("players.show.failed_create_notification"));
                }
            } catch {}

            this.loadingNotifications = false;
        },
        async deleteNotification(id) {
            if (this.loadingNotifications || !confirm(this.t("players.show.confirm_delete_notification"))) {
                return;
            }

            this.loadingNotifications = true;

            try {
                const response = await _delete(`/players/${this.player.licenseIdentifier}/notifications/${id}`);

                if (response?.status) {
                    this.notifications = response.data;
                } else {
                    alert(response?.error || this.t("players.show.failed_delete_notification"));
                }
            } catch (e) {
                console.error(e)
            }

            this.loadingNotifications = false;
        },
        warningMessageChanged() {
            const message = this.form.warning.message;
            const type = this.form.warning.warning_type;

            if (!message || !type) {
                sessionStorage.removeItem(`warning_${this.player.licenseIdentifier}`);

                return;
            }

            sessionStorage.setItem(`warning_${this.player.licenseIdentifier}`, JSON.stringify({
                message,
                type
            }));
        },
        resolveStaffStatistics(pSource) {
            return _get(`/players/${this.player.licenseIdentifier}/statistics/${pSource}`);
        },
        showGlobalBans() {
            this.showingGlobalBans = true;
        },
        updatePlayerTime() {
            const timezoneOffset = this.player.variables?.tz_offset;

            if (typeof timezoneOffset !== "number") {
                return;
            }

            this.playerTime = dayjs().utcOffset(timezoneOffset * -1).format('h:mm:ss A');
        },
        estimateRatio(pRatio) {
            if (!pRatio) return '???';

            for (let w = 1; w <= 50; w++) {
                for (let h = 1; h <= 50; h++) {
                    const ratio = w / h;

                    if (Math.abs(ratio - pRatio) < 0.05) {
                        return `${w}:${h} (${pRatio.toFixed(2)})`;
                    }
                }
            }

            return `??? (${pRatio.toFixed(2)})`;
        },
        showAntiCheatMetadata(event, eventData) {
            event.preventDefault();

            this.antiCheatMetadata = true;

            this.antiCheatMetadataImage = eventData.screenshot_url;
            this.antiCheatMetadataJSON = eventData.metadata;
        },
        async unbanPlayer() {
            if (this.isLoading) return;

            if (!this.activeBan.issuer && !this.isConfirmingUnban && (this.confirmedAccuracy || this.prettyHighAccuracy)) {
                this.isConfirmingUnban = true;
                this.confirmingUnbanInput = "";

                return;
            }

            this.isConfirmingUnban = false;

            this.isLoading = true;

            // Send request.
            await this.$inertia.delete(`/players/${this.player.licenseIdentifier}/bans/${this.activeBan.id}`);

            this.isLoading = false;
        },
        async scheduleUnban() {
            if (this.isLoading || !this.scheduledUnbanDate) return;

            const timestamp = dayjs.utc(this.scheduledUnbanDate).unix();

            if (timestamp * 1000 < Date.now()) return;

            this.isLoading = true;
            this.isSchedulingUnban = false;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/bans/${this.activeBan.id}/schedule`, {
                timestamp: timestamp
            });

            this.isLoading = false;
        },
        async updateCommands() {
            if (this.isLoading) return;

            this.isEnablingCommands = false;

            this.isLoading = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/update_commands`, {
                enabledCommands: this.enabledCommands,
            });

            this.isLoading = false;
        },
        async unlinkHWID() {
            if (this.isLoading) return;

            if (!confirm(this.t('players.show.unlink_confirm'))) {
                return;
            }

            this.isLoading = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/unlink_hwid/${this.hwidBan.license}`);

            this.isLoading = false;

            this.hwidBan = null;

            this.loadHWIDLink();
        },
        async unlinkIdentifiers(pLicenseIdentifier) {
            if (this.isLoading) return;

            if (!confirm(this.t('players.show.unlink_confirm'))) {
                return;
            }

            this.isShowingLinked = false;

            this.isLoading = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/unlink/${pLicenseIdentifier}`);

            this.isLoading = false;
        },
        cheatDocs(pNote) {
            pNote = pNote.trim();

            if (pNote.startsWith('Anti-Cheat: Invalid Damage Modifier')) {
                return '/docs/damage_modifier';
            }

            return false;
        },
        async loadGlobalBans() {
            try {
                const url = `https://op-framework.com/api/fivem/servers/bans/${this.player.licenseIdentifier}`;

                const response = await _get(url, {
                    _timeout: 3000
                });

                if (Array.isArray(response)) {
                    this.globalBans = response.filter(ban => !ban.serverId || !ban.serverId.startsWith(this.$page.auth.cluster));
                }
            } catch {}
        },
        async loadOPFWBan() {
            try {
                const url = `https://op-framework.com/api/cfx/player/${this.player.licenseIdentifier}/ban`;

                const response = await _get(url, {
                    _timeout: 3000,
                });

                if (response?.banned) {
                    this.opfwBanned = response.banned;
                    this.opfwBanned.appeal = `https://docs.google.com/forms/d/e/1FAIpQLSeZZnSHR6wdfQsbMow9pZ5Xo2rKmgCVIt5bVesVCAud_NB2KQ/viewform`;
                }
            } catch {}

            this.local.ban = this.localizeBan();

            this.loadingOpfwBan = false;
        },
        async loadIPInfo() {
            try {
                const data = await _get(`/players/${this.player.licenseIdentifier}/ip`);

                if (data?.success) {
                    this.isUsingVPN = data.is_vpn;
                }
            } catch (e) { }
        },
        async loadStatus() {
            this.statusLoading = true;

            const status = (await this.requestData(`/online/${this.player.licenseIdentifier}`)) || {};

            this.status = status[this.player.licenseIdentifier] || false;

            this.statusLoading = false;
        },
        async loadHWIDLink() {
            if (this.activeBan) {
                return;
            }

            this.loadingHWIDLink = true;

            try {
                const response = await _get(`/players/${this.player.licenseIdentifier}/linked_hwid`);

                if (response?.status) {
                    const data = response.data;

                    this.hwidBan = data;
                }
            } catch (e) {
            }

            this.loadingHWIDLink = false;
        },
        formatScreenCaptureLogs(logs) {
            if (!logs) return false;

            return logs.map(log => {
                return log.replace(/^(\[\d+:\d+:\d+\]) (.+?)$/gm, (match, time, message) => {
                    message = message.replace(/\d+/g, number => {
                        number = Number.parseInt(number).toLocaleString("en-US");

                        return `<span class="text-teal-700 dark:text-teal-300">${number}</span>`;
                    });

                    return `<span class="text-gray-700 dark:text-gray-300">${time}</span> ${message}`;
                });
            });
        },
        async createScreenCapture() {
            if (this.screenCaptureStatus) {
                return;
            }

            this.captureData.duration = Number.parseInt(this.captureData.duration) || 0;

            if (!Number.isInteger(this.captureData.duration) || this.captureData.duration < 1 || this.captureData.duration > 30) {
                alert(this.t("screenshot.invalid_duration"));

                return;
            }

            const interval = setInterval(() => {
                this.captureRemaining--;

                if (this.captureRemaining === 0) {
                    this.screenCaptureStatus = "processing";

                    clearInterval(interval);
                }
            }, 100);

            this.screenCaptureLogs = null;

            this.captureRemaining = this.captureData.duration * 10;
            this.screenCaptureStatus = "capturing";

            try {
                const result = await _post(`/api/capture/${this.$page.serverName}/${this.status.source}/${this.captureData.duration}`, {
                    _timeout: this.captureData.duration + 20000
                });

                clearInterval(interval);

                if (result.status) {
                    console.info(`Screen capture of ID ${this.status.source}`, result.data.url, result.data.license);

                    this.screenCaptureVideo = result.data.url;
                    this.screenCaptureLogs = this.formatScreenCaptureLogs(result.data.logs);
                } else {
                    this.screenshotError = result?.message || this.t('screenshot.screencapture_failed');
                }
            } catch (e) {
                clearInterval(interval);

                this.screenCaptureError = this.t('screenshot.screencapture_failed');
            }

            this.screenCaptureStatus = false;
        },
        stopContinuousScreenshot() {
            this.continuouslyScreenshotting = false;
        },
        startContinuousScreenshot() {
            if (this.continuousScreenshotThread) {
                return;
            }

            this.continuouslyScreenshotting = true;
            this.continuousScreenshotThread = true;

            const wait = ms => new Promise(resolve => setTimeout(resolve, ms));

            const doScreenshot = () => {
                this.createScreenshot(async success => {
                    if (!success || !this.continuouslyScreenshotting) {
                        this.continuouslyScreenshotting = false;
                        this.continuousScreenshotThread = false;

                        return;
                    }

                    this.nextContinuousScreenshot = 3.0;

                    while (this.nextContinuousScreenshot > 0) {
                        await wait(100);

                        this.nextContinuousScreenshot -= 0.1;

                        if (!this.continuouslyScreenshotting) {
                            this.continuousScreenshotThread = false;

                            return;
                        }
                    }

                    doScreenshot();
                });
            };

            doScreenshot();
        },
        async createScreenshot(cb, shortLifespan) {
            if (this.isScreenshotLoading) {
                cb?.(false);

                return;
            }
            this.isScreenshotLoading = true;
            this.screenshotError = null;

            this.screenCaptureLogs = null;
            this.screenshotLicense = null;
            this.screenshotFlags = null;

            try {
                const result = await _post(`/api/screenshot/${this.$page.serverName}/${this.status.source}${shortLifespan ? '?short=1' : ''}`);

                this.isScreenshotLoading = false;

                if (result.status) {
                    console.info(`Screenshot of ID ${this.status.source}`, result.data.url, result.data.license);

                    this.screenshotImage = result.data.url;
                    this.screenshotLicense = result.data.license;

                    this.screenshotFlags = result.data.flags;
                    this.screenCaptureLogs = this.formatScreenCaptureLogs(result.data.logs);

                    cb?.(true);
                } else {
                    this.screenshotError = result?.message || this.t('map.screenshot_failed');

                    cb?.(false);
                }
            } catch (e) {
                console.error(e);

                this.screenshotError = this.t('map.screenshot_failed');

                this.isScreenshotLoading = false;

                cb?.(false);
            }
        },
        async showDiscord(e) {
            e.preventDefault();
            this.isShowingDiscordLoading = true;
            this.isShowingDiscord = true;

            this.discordAccounts = [];

            try {
                const data = await _get(`/players/${this.player.licenseIdentifier}/discord`);

                if (data?.status) {
                    const accounts = data.data;

                    this.discordAccounts = accounts;
                } else {
                    this.discordAccounts = [];
                }
            } catch (e) {
                this.discordAccounts = [];
            }

            this.isShowingDiscordLoading = false;
        },
        async showLinked() {
            if (this.isLoading) return;

            this.isShowingLinkedLoading = true;
            this.isShowingLinked = true;

            this.linkedAccounts.total = 0;
            this.linkedAccounts.linked = [];

            try {
                const data = await _get(`/players/${this.player.licenseIdentifier}/linked`);

                if (data?.status) {
                    const linked = data.data;

                    this.linkedAccounts.total = linked.total;
                    this.linkedAccounts.linked = linked.linked;
                } else {
                    this.linkedAccounts.total = 0;
                    this.linkedAccounts.linked = [];
                }
            } catch (e) {
                this.linkedAccounts.total = 0;
                this.linkedAccounts.linked = [];
            }

            this.isShowingLinkedLoading = false;
        },
        canSeeAntiCheat() {
            return this.perm.check(this.perm.PERM_ANTI_CHEAT);
        },
        async showAntiCheat() {
            this.isShowingAntiCheatLoading = true;
            this.isShowingAntiCheat = true;

            this.antiCheatEvents = [];

            try {
                const data = await _get(`/players/${this.player.licenseIdentifier}/anti_cheat`);

                if (data?.status) {
                    this.antiCheatEvents = data.data;
                }
            } catch (e) { }

            this.isShowingAntiCheatLoading = false;
        },
        getWarningTypeIcon(type) {
            const label = this.t(`players.show.warning_type.${type}`);

            switch (type) {
                case 'strike':
                    return `<span class="cursor-help text-red-400"><i class="fas fa-bolt" title="${label}"></i></span>`;
                case 'warning':
                    return `<span class="cursor-help text-orange-400"><i class="fas fa-exclamation-triangle" title="${label}"></i></span>`;
                case 'note':
                    return `<span class="cursor-help text-yellow-400"><i class="fas fa-sticky-note" title="${label}"></i></span>`;
                case 'system':
                    return `<span class="cursor-help text-blue-400"><i class="fas fa-robot" title="${label}"></i></span>`;
                case 'hidden':
                    return `<span class="cursor-help text-pink-400"><i class="fas fa-eye-slash" title="${label}"></i></span>`;
            }

            return '';
        },
        localizeBan() {
            if (!this.activeBan) {
                return '';
            }

            const suffix = this.opfwBanned ? '_op' : '';

            return this.activeBan.expireAt
                ? this.t(`players.show.ban${suffix}`, this.formatBanCreator(this.activeBan.issuer), this.$options.filters.formatTime(this.activeBan.expireAt))
                : this.t(`players.ban.forever${suffix}`, this.formatBanCreator(this.activeBan.issuer));
        },
        async pmPlayer() {
            // Send request.
            await this.$inertia.post(`/players/${this.player.overrideLicense ? this.player.overrideLicense : this.player.licenseIdentifier}/staff_pm`, this.form.pm, { preserveScroll: true });

            // Reset.
            this.isStaffPM = false;
            this.form.pm.message = null;
        },
        async removeBanException() {
            if (this.updatingBanException) return;

            if (!confirm(this.t('players.show.remove_ban_exception_confirm'))) {
                return;
            }

            this.updatingBanException = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/update_ban_exception`, {
                twitch: false
            }, { preserveScroll: true });

            this.updatingBanException = false;
        },
        async setBanException() {
            if (this.updatingBanException) return;

            this.banExceptionTwitch = this.banExceptionTwitch.trim();

            if (!this.banExceptionTwitch) {
                this.removeBanException();

                this.editingBanException = false;

                return;
            }

            const twitch = this.banExceptionTwitch.match(/(?<=^https:\/\/(?:www\.)?twitch\.tv\/)\w+$/mi)?.shift();

            if (!twitch) {
                alert(this.t('players.show.invalid_twitch_id'));

                return;
            }

            this.updatingBanException = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/update_ban_exception`, {
                twitch: twitch
            }, { preserveScroll: true });

            this.updatingBanException = false;
            this.editingBanException = false;
        },
        editBanException() {
            if (this.player.streamerException) {
                this.banExceptionTwitch = `https://twitch.tv/${this.player.streamerException}`;
            } else {
                this.banExceptionTwitch = "";
            }

            this.editingBanException = true;
        },
        async updateWhitelistStatus(status) {
            if (!confirm(this.t(`players.show.${status ? 'whitelist_confirm' : 'unwhitelist_confirm'}`))) {
                return;
            }

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/update_whitelist`, {
                status: status
            }, { preserveScroll: true });
        },
        async unmutePlayer() {
            if (!confirm(this.t('players.show.unmute_confirm'))) {
                return;
            }

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/update_mute`, {
                status: false
            }, { preserveScroll: true });
        },
        async removeTag() {
            this.isTagging = false;

            // Send request.

            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/update_tag`, {
                tag: false
            }, { preserveScroll: true });
        },
        async addTag() {
            const tag = this.tagCategory === 'custom' ? this.tagCustom.trim() : this.tagCategory;

            if (!tag) {
                return;
            }

            this.isTagging = false;

            // Send request.

            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/update_tag`, {
                tag: tag
            }, { preserveScroll: true });
        },
        async kickPlayer() {
            if (!confirm(this.t('players.show.kick_confirm'))) {
                this.isKicking = false;
                return;
            }

            // Send request.
            await this.$inertia.post(`/players/${this.player.overrideLicense ? this.player.overrideLicense : this.player.licenseIdentifier}/kick`, this.form.kick, { preserveScroll: true });

            // Reset.
            this.isKicking = false;
            this.form.kick.reason = null;
        },
        async revivePlayer() {
            if (!confirm(this.t('players.show.revive_confirm'))) {
                return;
            }

            // Send request.
            await this.$inertia.post(`/players/${this.player.overrideLicense ? this.player.overrideLicense : this.player.licenseIdentifier}/revive`, { preserveScroll: true });
        },
        async unloadCharacter() {
            if (!confirm(this.t('players.show.unload_confirm'))) {
                return;
            }

            // Send request.
            await this.$inertia.post(`/players/${this.player.overrideLicense ? this.player.overrideLicense : this.player.licenseIdentifier}/unload_character`, this.form.unload, { preserveScroll: true });

            this.form.unload.message = this.t('players.show.unload_default');
            this.form.unload.character = null;
            this.isUnloading = false;
        },
        async deleteCharacter(e, characterId) {
            e.preventDefault();

            if (!confirm(this.t('players.show.delete_character'))) {
                return;
            }

            // Send request.
            await this.$inertia.delete(`/players/${this.player.licenseIdentifier}/characters/${characterId}`);
        },
        async submitBan() {
            // Default expiration.
            let expire = null;

            // Calculate expire relative to now in seconds if temp ban.
            if (this.isTempBanning) {
                const nowUnix = dayjs().unix();

                if (this.isTempSelect) {
                    const expireUnix = dayjs(`${this.form.ban.expireDate} ${this.form.ban.expireTime}`).unix();
                    expire = expireUnix - nowUnix;
                } else {
                    let val = Number.parseInt($('#ban-value').val());

                    if (val <= 0) {
                        return;
                    }

                    switch ($('#ban-type').val()) {
                        case 'hour':
                            val *= 60 * 60;
                            break;
                        case 'day':
                            val *= 60 * 60 * 24;
                            break;
                        case 'week':
                            val *= 60 * 60 * 24 * 7;
                            break;
                        case 'month':
                            val *= 60 * 60 * 24 * 7 * 30;
                            break;
                        default:
                            return;
                    }

                    expire = val;
                }
            }

            if (this.isBanLoading) return;

            this.isBanLoading = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/bans`, { ...this.form.ban, expire }, { preserveScroll: true });

            this.local.ban = this.localizeBan();

            // Reset.
            this.isBanLoading = false;
            this.isBanning = false;
            this.isTempBanning = false;
            this.isTempSelect = true;
            this.form.ban.reason = null;
            this.form.ban.expire = null;
            this.form.ban.expireDate = null;
            this.form.ban.expireTime = null;
        },
        async submitWarning() {
            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/warnings`, this.form.warning, { preserveScroll: true });

            // Reset.
            this.form.warning.message = null;

            // Remove saved message.
            sessionStorage.removeItem(`warning_${this.player.licenseIdentifier}`);
        },
        async editWarning(id, warningType) {
            if (this.deletingWarnings) return;

            // Send request.
            await this.$inertia.put(`/players/${this.player.licenseIdentifier}/warnings/${id}`, {
                message: $(`#warning_${id}`).val(),
                warning_type: warningType,
            }, { preserveScroll: true });

            // Reset.
            this.warningEditId = 0;
        },
        async deleteWarning(id) {
            if (this.deletingWarnings || !confirm(this.t('players.show.delete_warning'))) {
                return;
            }

            // Send request.
            await this.$inertia.delete(`/players/${this.player.licenseIdentifier}/warnings/${id}`, {}, { preserveScroll: true });
        },
        async refreshWarning(id) {
            if (this.refreshingWarning) return;

            this.refreshingWarning = id;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/warnings/${id}/refresh`, {}, { preserveScroll: true });

            this.refreshingWarning = false;
        },
        async deleteSelectedWarnings() {
            if (this.deletingWarnings || this.selectedWarnings.length === 0) return;

            if (!confirm(this.t('players.show.delete_selected_warnings', this.selectedWarnings.length))) return;

            this.deletingWarnings = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/warnings/bulk`, {
                ids: this.selectedWarnings
            }, { preserveScroll: true });

            this.deletingWarnings = false;
            this.selectedWarnings = [];
        },
        selectWarning(id) {
            if (this.deletingWarnings) return;

            const index = this.selectedWarnings.indexOf(id);

            if (index === -1) {
                this.selectedWarnings.push(id);
            } else if (this.selectedWarnings.length < 10) {
                this.selectedWarnings.splice(index, 1);
            }
        },
        hideDeleted(e) {
            e.preventDefault();

            this.isShowingDeletedCharacters = !this.isShowingDeletedCharacters;
            if (this.isShowingDeletedCharacters) {
                $('.card-deleted').removeClass('hidden');
            } else {
                $('.card-deleted').addClass('hidden');
            }
        },
        copyLicense(e) {
            const button = $(e.target).closest('.badge');

            this.copyToClipboard(this.player.licenseIdentifier);

            $('span', button).text(this.t('global.copied'));

            setTimeout(() => {
                $('span', button).text(this.t('players.show.copy_license'));
            }, 1500);
        },
        copyText(e, text) {
            e.preventDefault();
            const button = $(e.target).closest('a');

            this.copyToClipboard(text)

            button.removeClass('bg-blue-800');
            button.addClass('bg-green-600');

            setTimeout(() => {
                button.removeClass('bg-green-600');
                button.addClass('bg-blue-800');
            }, 500);
        },
        isAutomatedWarning(warning) {
            const message = warning.message;
            const type = warning.warningType;

            if (message?.includes('This warning was generated automatically')) return true;
            if (message?.startsWith('I scheduled the removal of this players ban for')) return true;
            if (message?.startsWith('I removed this players ban')) return true;
            if (message?.startsWith('Smurf account detected. Ban applied,')) return true;

            return type === 'system';
        },
        formatWarning(warning) {
            warning = warning.replace(/(https?:\/\/(.+?)\/players\/)?(steam:\w{15})/gmi, (full, _ignore, host, steam) => {
                const url = full?.startsWith("http") ? full : `/players/${steam}`;
                const cluster = host ? host.split(".")[0].replace("localhost", "c1") : this.$page?.auth?.cluster;

                return `<a href="${url}" target="_blank" class="text-yellow-600 dark:text-yellow-400">${cluster.toLowerCase()}/${steam.toLowerCase()}</a>`;
            });

            warning = warning.replace(/(https?:\/\/(.+?)\/players\/)?(license:\w{40})/gmi, (full, _ignore, host, license) => {
                const url = full?.startsWith("http") ? full : `/players/${license}`;
                const cluster = host ? host.split(".")[0].replace("localhost", "c1") : this.$page?.auth?.cluster;

                return `<a href="${url}" target="_blank" class="text-yellow-600 dark:text-yellow-400">${cluster.toLowerCase()}/${license.toLowerCase()}</a>`;
            });

            return warning;
        },
        formatBanCreator(creator) {
            if (!creator) {
                return this.t('global.system');
            }
            return creator;
        },
        copyWarning(warning) {
            this.copyToClipboard(warning.message);
        },
        asyncLoadImage(url) {
            return new Promise((resolve, reject) => {
                const img = new Image();

                img.onload = resolve;
                img.onerror = reject;

                setTimeout(() => {
                    if (!img.complete || !img.naturalWidth) {
                        reject();

                        img.src = "";
                    }
                }, 4000);

                img.src = url;
            });
        },
        async toggleReaction(warning, emoji) {
            if (this.isReacting[warning.id]) return;

            this.isReacting[warning.id] = true;

            try {
                const data = await _post(`/players/${this.player.licenseIdentifier}/warnings/${warning.id}/react`, {
                    emoji: emoji
                });

                if (data?.status) {
                    warning.reactions = data.data;
                    warning.hover = false;
                }
            } catch (e) { }

            this.$nextTick(() => {
                delete this.isReacting[warning.id];
            });
        },
        randomizeReaction(warning) {
            let available = this.reactions.filter(reaction => !warning.reactions.all[reaction]);

            if (available.length > 1 && available.includes(warning.random)) {
                available = available.filter(reaction => reaction !== warning.random);
            }

            warning.random = available[Math.floor(Math.random() * available.length)];

            return warning.random;
        },
        hoveringReaction(warning) {
            if (this.isReacting[warning.id] || warning.hover || warning.hoverLoading) return;

            warning.hoverLoading = true;
            warning.hover = warning.hover || null;

            _get(`/players/${this.player.licenseIdentifier}/warnings/${warning.id}/react`).then(data => {
                if (!data.status || warning.hover === false) return;

                warning.hover = data.data;
            }).finally(() => {
                warning.hoverLoading = false;
            });
        },
        smartJoin(array) {
            return array.join(', ').replace(/, ([^,]*)$/, ' and $1');
        },
        highlightUnicode(text) {
            return text.split('').map(char => {
                if (char.match(/[a-z0-9!"#$%&'()*+,.\/:;<=>?@\[\] ^_`{|}~-]/)) return char;

                return `\\u${(`0000${char.charCodeAt(0).toString(16)}`).slice(-4)}`;
            }).join('');
        }
    },
    mounted() {
        const url = window.location.href.replace(/(?<=\/players\/).+?(?=[/?#]|$)/gm, this.player.licenseIdentifier);
        if (url !== window.location.href) {
            window.history.replaceState({}, document.title, url);
        }

        if (this.kickReason) {
            this.isKicking = true;
            this.form.kick.reason = this.kickReason;
        }

        const savedMessage = sessionStorage.getItem(`warning_${this.player.licenseIdentifier}`);

        if (savedMessage) {
            try {
                const json = JSON.parse(savedMessage);

                this.form.warning.message = json.message;
                this.form.warning.warning_type = json.type;
            } catch (e) { }
        }

        // Delay loading of extra data since it blocks other resources from loading
        setTimeout(() => {
            this.loadHWIDLink();
            this.loadStatus();
            this.loadGlobalBans();
            this.loadOPFWBan();
            this.loadIPInfo();
        }, 500);

        this.updatePlayerTime();

        setInterval(() => {
            this.updatePlayerTime();
        }, 10000);

        // Delay loading of character images since it blocks other resources from loading
        $(document).ready(() => {
            setTimeout(() => {
                $("img[data-lazy]").each((i, img) => {
                    const url = $(img).data("lazy");

                    if (url.includes("screenshot-undefined")) {
                        $(img).attr("src", "/images/no_mugshot.png");

                        return;
                    }

                    this.asyncLoadImage(url).then(() => {
                        $(img).attr("src", url);
                    }).catch(() => {
                        $(img).attr("src", "/images/no_mugshot.png");
                    });
                });
            }, 250);
        });

        $(document).on("visibilitychange", e => {
            if (document.visibilityState !== "visible") {
                this.continuouslyScreenshotting = false;
            }
        });
    },
    updated() {
        $(".separator").each(() => {
            while ($(this).next().hasClass("separator")) {
                $(this).next().remove();
            }

            $(".separator:last-child").remove();
        });
    }
};
</script>
