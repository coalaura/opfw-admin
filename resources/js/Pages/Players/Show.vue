<template>
    <div>
        <portal to="title">
            <div class="flex justify-between mobile:flex-wrap mt-8 gap-4 w-full">
                <div class="flex gap-4">
                    <!-- Copy License -->
                    <div class="cursor-pointer text-2xl flex items-center" :title="t('players.show.copy_license')" @click="copyLicense">
                        <i class="fas fa-copy relative top-2px"></i>
                    </div>

                    <CountryFlag :country="flagFromTZ(player.variables.timezone)" :title="player.variables.timezone" class="rounded-sm" v-if="player.variables && player.variables.timezone" />

                    <h1 class="dark:text-white">
                        {{ player.safePlayerName }}
                    </h1>

                    <div v-if="loadingHWIDLink || loadingExtraData" class="relative text-pink-600 dark:text-pink-400" :title="t('players.show.loading_extra')">
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

                    <badge class="border-gray-200 overflow-hidden bg-center bg-cover w-32 cursor-help" style="background-image: url('/images/wide_putin.webp')" v-if="player.stretchedRes" :title="t('players.show.stretch_res', estimateRatio(player.stretchedRes.aspectRatio), estimateRatio(player.stretchedRes.pixelRatio))"></badge>

                    <badge class="border-gray-200 bg-secondary dark:bg-dark-secondary" :title="formatSecondDiff(player.playTime)" v-html="local.played"></badge>

                    <badge class="border-gray-200 bg-secondary dark:bg-dark-secondary italic" v-if="player.averagePing" :title="t('players.show.average_ping')">
                        <i class="fas fa-table-tennis"></i>
                        {{ player.averagePing }}ms
                    </badge>

                    <badge class="border-gray-200 bg-secondary dark:bg-dark-secondary italic" v-if="player.averageFps" :title="t('players.show.average_fps')">
                        <i class="fas fa-stopwatch"></i>
                        {{ player.averageFps }}fps
                    </badge>

                    <badge class="border-pink-300 bg-pink-200 dark:bg-pink-700" v-if="player.tag">
                        <span class="font-semibold">{{ player.tag }}</span>
                    </badge>

                    <badge :class="`border-${echo.color}-300 bg-${echo.color}-200 dark:bg-${echo.color}-700 ${echo.raw ? 'cursor-pointer' : ''}`" v-if="echo" :click="showEchoInfo" :title="echo.title">
                        <i :class="echo.icon" class="mr-1"></i>
                        <span class="font-semibold">{{ t('players.show.echo_info') }}</span>
                    </badge>

                    <badge class="border-red-300 bg-red-200 dark:bg-red-700 cursor-pointer" v-if="globalBans.length > 0" :title="t('players.show.global_title', globalBans.length)" :click="showGlobalBans">
                        <i class="fas fa-x-ray mr-1"></i>
                        <span class="font-semibold">{{ t('players.show.global_info') }}</span>
                    </badge>
                </div>
            </div>
            <div class="text-sm italic mt-4">
                <span class="block" v-if="player.playerName !== player.safePlayerName">
                    <span class="font-bold">{{ t('players.show.original_name') }}:</span>
                    <span class="bg-gray-200 dark:bg-gray-700 px-1 whitespace-pre">{{ player.playerName }}</span>
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
                <div class="text-sm italic mt-1">
                    <span class="block" v-if="getPlayerMetadata()">
                        <span class="font-bold">{{ t('players.show.metadata') }}:</span>
                        {{ getPlayerMetadata() }}
                    </span>
                    <span class="block">
                        <span class="font-bold">{{ t('players.show.enabled_commands') }}:</span>
                        {{ player.enabledCommands.length > 0 ? player.enabledCommands.map(e => '/' + e).join(", ") : "N/A" }}

                        <a href="#" class="text-indigo-600 dark:text-indigo-400" @click="$event.preventDefault(); isEnablingCommands = true" v-if="$page.auth.player.isSuperAdmin">{{ t('players.show.edit') }}</a>
                    </span>
                    <span class="block">
                        <span class="font-bold">{{ t('players.show.recent_playtime') }}:</span>
                        {{ formatSecondDiff(player.recentPlayTime) }}
                        <span class="italic text-gray-600 dark:text-gray-400">{{ t('players.show.recent_playtime_after') }}</span>
                    </span>
                    <span class="block" v-if="player.lastConnection">
                        <span class="font-bold">{{ t('players.show.last_connection') }}:</span>
                        {{ player.lastConnection | formatTime(true) }} ({{ $moment(player.lastConnection).fromNow() }})
                    </span>
                </div>
                <div class="text-sm italic mt-1">
                    <span class="block" v-if="player.countryName" :title="t('players.show.country_detail')">
                        <span class="font-bold">{{ t('players.show.country_name') }}:</span>
                        {{ player.countryName }}
                    </span>

                    <span class="block" v-if="player.variables && player.variables.timezone && typeof player.variables.timezoneOffset === 'number'">
                        <span class="font-bold">{{ t('players.show.timezone') }}:</span>
                        {{ player.variables.timezone }} - <span class="font-semibold">{{ playerTime }}</span>
                    </span>

                    <span class="block" v-if="player.variables && player.variables.screenWidth && player.variables.screenHeight">
                        <span class="font-bold">{{ t('players.show.resolution') }}:</span>
                        {{ player.variables.screenWidth + "x" + player.variables.screenHeight }}
                    </span>
                    <span class="block" v-if="player.variables && player.variables.ofFingerprint && this.perm.check(this.perm.PERM_LINKED)">
                        <span class="font-bold">{{ t('players.show.ofFingerprint') }}:</span>
                        <a :href="'/linked_print/' + player.licenseIdentifier" target="_blank" class="text-indigo-600 dark:text-indigo-400 !no-underline">{{ player.variables.ofFingerprint }}</a>
                    </span>
                </div>
            </div>
        </portal>

        <div class="flex flex-wrap justify-between mb-6">
            <div class="mb-3 flex flex-wrap gap-3">
                <!-- Debugger -->
                <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale px-4 py-2" :title="t('global.debugger_title')" v-if="player.isDebugger && !player.isRoot">
                    <i class="fas fa-toolbox mr-1"></i>
                    <span class="font-semibold">{{ t('global.debugger') }}</span>
                </badge>

                <!-- Panel drug department -->
                <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale px-4 py-2" v-if="$page.auth.player.isSuperAdmin && player.panelDrugDepartment">
                    <i class="fas fa-tablets mr-1"></i>
                    <span class="font-semibold" :title="t('players.show.drug_department_title')">{{ t('players.show.drug_department') }}</span>
                </badge>

                <!-- Whitelisted -->
                <badge class="border-green-200 bg-success-pale dark:bg-dark-success-pale px-4 py-2" v-if="whitelisted">
                    <i class="fas fa-clipboard-check mr-1"></i>
                    <span class="font-semibold">{{ t('global.whitelisted') }}</span>
                </badge>

                <!-- Streamer Ban exception -->
                <a class="px-4 py-2 font-semibold border-2 rounded bg-yellow-100 dark:bg-yellow-700 border-yellow-200 flex items-center gap-1" :href="'https://twitch.tv/' + player.streamerException" target="_blank" v-if="player.streamerException" :title="t('players.show.streamer_exception_title', player.streamerException)">
                    <i class="fab fa-twitch mr-1"></i>
                    {{ t('players.show.streamer_exception') }}
                </a>

                <!-- Blacklisted -->
                <badge class="border-red-200 bg-danger-pale dark:bg-dark-danger-pale px-4 py-2" v-if="blacklisted">
                    <i class="fas fa-hand-paper mr-1"></i>
                    <span class="font-semibold">{{ t('global.blacklisted') }}</span>
                </badge>

                <!-- Soft Ban -->
                <badge class="border-red-200 bg-danger-pale dark:bg-dark-danger-pale px-4 py-2" v-if="this.perm.check(this.perm.PERM_SOFT_BAN) && player.isSoftBanned">
                    <i class="fas fa-feather-alt mr-1"></i>

                    <span class="font-semibold">{{ t('global.soft_banned') }}</span>

                    <a href="#" @click="removeSoftBan($event)" class="ml-1 text-white" :title="t('players.show.remove_soft_ban')">
                        <i class="fas fa-times"></i>
                    </a>
                </badge>
            </div>

            <div class="mb-3 flex flex-wrap justify-end gap-3">
                <button class="px-4 py-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger flex items-center gap-1" @click="addSoftBan()" v-if="this.perm.check(this.perm.PERM_SOFT_BAN) && !player.isSoftBanned">
                    <i class="fas fa-smoking-ban"></i>
                    {{ t('players.show.add_soft_ban') }}
                </button>

                <!-- StaffPM -->
                <button class="px-4 py-2 font-semibold text-white rounded bg-blue-600 dark:bg-blue-500 flex items-center gap-1" @click="isStaffPM = true" v-if="status">
                    <i class="fas fa-envelope-open-text"></i>
                    {{ t('players.show.staffpm') }}
                </button>
                <!-- Kicking -->
                <button class="px-4 py-2 font-semibold text-white rounded bg-yellow-600 dark:bg-yellow-500 flex items-center gap-1" @click="isKicking = true" v-if="status">
                    <i class="fas fa-user-minus"></i>
                    {{ t('players.show.kick') }}
                </button>
                <!-- Edit Ban -->
                <inertia-link class="px-4 py-2 font-semibold text-white rounded bg-yellow-600 dark:bg-yellow-500 flex items-center gap-1" v-bind:href="'/players/' + player.licenseIdentifier + '/bans/' + player.ban.id + '/edit'" v-if="player.isBanned && (!player.ban.locked || this.perm.check(this.perm.PERM_LOCK_BAN))">
                    <i class="fas fa-edit"></i>
                    {{ t('players.show.edit_ban') }}
                </inertia-link>
                <!-- Unbanning -->
                <button class="px-4 py-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger flex items-center gap-1" @click="unbanPlayer()" v-if="player.isBanned && (!player.ban.locked || this.perm.check(this.perm.PERM_LOCK_BAN))">
                    <i class="fas fa-lock-open"></i>
                    {{ t('players.show.unban') }}
                </button>
                <!-- Schedule Unban -->
                <button class="px-4 py-2 font-semibold text-white rounded bg-yellow-600 dark:bg-yellow-500 flex items-center gap-1" @click="isSchedulingUnban = true" v-if="player.isBanned && !player.ban.scheduled">
                    <i class="fas fa-calendar-day"></i>
                    {{ t('players.show.schedule_unban') }}
                </button>
                <!-- Remove Scheduled Unban -->
                <inertia-link class="px-4 py-2 font-semibold text-white rounded bg-purple-600 dark:bg-purple-500 flex items-center gap-1" method="POST" v-bind:href="'/players/' + player.licenseIdentifier + '/bans/' + player.ban.id + '/unschedule'" v-if="player.isBanned && player.ban.scheduled">
                    <i class="fas fa-calendar-times"></i>
                    {{ t('players.show.remove_schedule') }}
                </inertia-link>
                <!-- Banning -->
                <button class="px-4 py-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger flex items-center gap-1" @click="isBanning = true" v-else-if="!player.isBanned">
                    <i class="fas fa-gavel"></i>
                    {{ t('players.show.issue') }}
                </button>
                <!-- Lock ban -->
                <inertia-link class="px-4 py-2 font-semibold text-white rounded bg-purple-600 dark:bg-purple-500 flex items-center gap-1" method="POST" v-bind:href="'/players/' + player.licenseIdentifier + '/bans/' + player.ban.id + '/lock'" v-if="player.isBanned && !player.ban.locked && this.perm.check(this.perm.PERM_LOCK_BAN)">
                    <i class="fas fa-lock"></i>
                    {{ t('players.show.lock_ban') }}
                </inertia-link>
                <!-- Unlock ban -->
                <inertia-link class="px-4 py-2 font-semibold text-white rounded bg-purple-600 dark:bg-purple-500 flex items-center gap-1" method="POST" v-bind:href="'/players/' + player.licenseIdentifier + '/bans/' + player.ban.id + '/unlock'" v-if="player.isBanned && player.ban.locked && this.perm.check(this.perm.PERM_LOCK_BAN)">
                    <i class="fas fa-lock-open"></i>
                    {{ t('players.show.unlock_ban') }}
                </inertia-link>
            </div>

            <!-- Small icon buttons top left -->
            <div class="absolute top-2 left-2 flex gap-2" v-if="this.perm.check(this.perm.PERM_LINKED)">
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

                <div class="w-px bg-white bg-opacity-30 h-full separator">&nbsp;</div>

                <!-- User Variables -->
                <button class="p-1 text-sm font-bold leading-4 text-center rounded border-teal-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="showingUserVariables = true" :title="t('players.show.user_variables')">
                    <i class="fas fa-memory mr-1"></i>
                    UVs
                </button>
            </div>

            <!-- Small icon buttons top right -->
            <div class="absolute top-2 right-2 flex gap-2 items-center">
                <!-- Staff statistics -->
                <template v-if="$page.auth.player.isSeniorStaff && player.isStaff">
                    <button class="p-1 text-sm font-bold leading-4 text-center rounded border-teal-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" :title="t('players.show.staff_stats')" @click="isShowingStaffStatistics = true">
                        <i class="fas fa-heartbeat mr-1"></i>
                        Stats
                    </button>

                    <div class="w-px bg-white bg-opacity-30 h-full separator">&nbsp;</div>
                </template>

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

                <!-- Edit Role -->
                <button class="p-1 text-sm font-bold leading-4 text-center rounded border-red-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="isRoleEdit = true" v-if="allowRoleEdit && !player.isSuperAdmin" :title="t('players.show.edit_role')">
                    <i class="fas fa-clipboard-list mr-1"></i>
                    Role
                </button>

                <!-- Add Tag -->
                <button class="p-1 text-sm font-bold leading-4 text-center rounded border-green-400 bg-secondary dark:bg-dark-secondary border-2 flex items-center" @click="isTagging = true" :title="t('players.show.edit_tag')" v-if="this.perm.check(this.perm.PERM_EDIT_TAG)">
                    <i class="fas fa-tag mr-1"></i>
                    Tag
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
                                <img :src="discord.avatar" class="rounded shadow border-2 border-gray-300 w-avatar mr-3" />
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
                <StatisticsTable source="staff" locale="players.show.source_" :currency="false" :resolve="resolveStaffStatistics" />
                <StatisticsTable source="staff_pm" locale="players.show.source_" :currency="false" :resolve="resolveStaffStatistics" />
                <StatisticsTable source="noclip" locale="players.show.source_" :currency="false" :resolve="resolveStaffStatistics" />
                <StatisticsTable source="spectate" locale="players.show.source_" :currency="false" :resolve="resolveStaffStatistics" />
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
                <div class="flex flex-col gap-3 text-left">
                    <div v-for="ban in globalBans" :key="ban.serverId" class="py-4 px-6 rounded-lg shadow-lg border-2 border-red-500 bg-red-100 dark:bg-red-950">
                        <h1 class="text-lg border-b border-gray-700 dark:border-gray-200">
                            {{ ban.serverName }}
                        </h1>

                        <div class="text-xs mb-1 flex justify-between opacity-70">
                            <span>{{ ban.timestamp * 1000 | formatTime }}</span>

                            <span v-if="ban.expire">{{ ban.expire | humanizeSeconds }}</span>
                            <span v-else>{{ t('players.show.indefinitely') }}</span>
                        </div>

                        <div class="italic">{{ ban.reason }}</div>
                    </div>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingGlobalBans = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- Echo Info -->
        <modal :show.sync="showingEchoInfo">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.show.echo_info') }}
                </h1>
            </template>

            <template #default>
                <div class="flex flex-col gap-3 text-left font-mono">
                    <div v-for="info in echo.raw" :key="info.identifier" class="py-4 px-6 rounded-lg shadow-lg border-2" :class="`border-${info.color}-500 bg-${info.color}-100 dark:bg-${info.color}-950`">
                        <h1 class="text-lg border-b mb-1 border-gray-700 dark:border-gray-200">
                            <a class="hover:underline" :href="'https://steamcommunity.com/profiles/' + info.steam" target="_blank">{{ info.identifier }}</a>
                        </h1>

                        <template v-if="info.lastScanned">
                            <p class="text-xs italic text-gray-600 dark:text-gray-400 mb-3">
                                <span class="font-semibold">{{ t('players.show.echo_last') }}:</span>
                                {{ info.lastScanned.local().format('dddd, Mo MMMM YYYY, HH:mm:ss') }}
                            </p>

                            <div class="flex gap-3">
                                <div class="py-1 px-2 bg-black dark:bg-white !bg-opacity-10 rounded font-semibold" v-if="info.clean > 0">
                                    <i class="fas fa-check mr-1"></i> {{ info.clean }} {{ t('players.show.echo_clean') }}
                                </div>

                                <div class="py-1 px-2 bg-black dark:bg-white !bg-opacity-10 rounded font-semibold" v-if="info.unusual > 0">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ info.unusual }} {{ t('players.show.echo_unusual') }}
                                </div>

                                <div class="py-1 px-2 bg-black dark:bg-white !bg-opacity-10 rounded font-semibold" v-if="info.detected">
                                    <i class="fas fa-skull-crossbones mr-1"></i> {{ info.detected }} {{ t('players.show.echo_detected') }}
                                </div>
                            </div>
                        </template>

                        <template v-else-if="info.failed">
                            <p class="italic text-gray-600 dark:text-gray-400 mt-3">
                                <span class="font-semibold">{{ t('players.show.echo_no_data') }}:</span>
                                <span class="italic">{{ info.failed }}</span>
                            </p>
                        </template>

                        <template v-else>
                            <p class="italic text-gray-600 dark:text-gray-400 mt-3">{{ t('players.show.echo_not_scanned') }}</p>
                        </template>
                    </div>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingEchoInfo = false">
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

        <!-- Role Edit -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-30" v-if="isRoleEdit">
            <div class="max-h-max overflow-y-auto shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-4 rounded w-small-alert">
                <h3 class="mb-2">{{ t('players.show.edit_role') }}</h3>
                <form class="space-y-6">
                    <div class="w-full p-3 flex justify-between">
                        <label class="mr-4 block w-1/4 text-center pt-2 font-bold">
                            {{ t('players.show.role') }}
                        </label>
                        <select class="block bg-gray-200 dark:bg-gray-600 rounded w-3/4 px-4 py-2" v-model="selectedRole">
                            <option value="player">{{ t('players.show.role_player') }}</option>
                            <option value="trusted">{{ t('players.show.role_trusted') }}</option>
                            <option value="staff">{{ t('players.show.role_staff') }}</option>
                            <option value="seniorStaff">{{ t('players.show.role_seniorStaff') }}</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end space-x-3">
                        <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" type="button" @click="isRoleEdit = false">
                            {{ t('global.cancel') }}
                        </button>
                        <button class="px-5 py-2 font-semibold text-white bg-green-500 rounded hover:bg-green-600" type="button" @click="updateRole">
                            <i class="fas fa-clipboard-list mr-1"></i>
                            {{ t('players.show.edit_role') }}
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

        <!-- Enabled commands -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-30" v-if="isEnablingCommands">
            <div class="max-h-max overflow-y-auto shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-4 rounded w-alert">
                <h3 class="mb-2">{{ t('players.show.update_commands') }}</h3>
                <form class="space-y-2">
                    <div class="flex items-center" v-for="command in commands" :key="command.name">
                        <input type="checkbox" v-model="command.enabled" :id="command.name" class="mr-2 outline-none">
                        <label>/{{ command.name }}</label>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center space-x-3 mt-4">
                        <button class="px-5 py-2 font-semibold text-white bg-green-500 rounded hover:bg-green-600" type="button" @click="updateCommands">
                            <i class="fas fa-tag mr-1"></i>
                            {{ t('players.show.update_commands') }}
                        </button>
                        <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" type="button" @click="isEnablingCommands = false">
                            {{ t('global.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

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
        <alert class="bg-rose-500 dark:bg-rose-500" v-if="player.mute">
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

                    <input class="w-full px-4 py-2 !border-red-400 !bg-red-500 !bg-opacity-10 border rounded my-3" v-model="confirmingUnbanInput" placeholder="confirm" :class="{'!border-lime-400 !bg-lime-500 !bg-opacity-10': confirmingUnbanInput === 'confirm'}" />
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

            <div class="mb-4 px-6 py-4 border-2 flex flex-col bg-purple-600 dark:bg-purple-500 rounded border-purple-800" v-if="player.isBanned && player.ban.scheduled">
                <span class="font-bold">
                    <i class="fas fa-calendar-day mr-1"></i>
                    {{ t('players.show.scheduled_unban') }}
                </span>
                <span class="text-sm italic" v-html="t('players.show.scheduled_details', scheduledUnban, scheduledUnbanIn)"></span>
            </div>

            <!-- Viewing -->
            <alert class="bg-danger dark:bg-dark-danger px-6 py-4" v-if="player.isBanned">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-lg font-semibold">
                        <i class="fas fa-shield-alt mr-1 cursor-help" v-if="player.streamerException" :title="t('players.show.streamer_exception_title', player.streamerException)"></i>

                        <span v-html="local.ban" :class="{'line-through': status && player.streamerException}"></span>
                    </h2>
                    <div class="font-semibold">
                        <i class="mr-1 fas fa-lock" v-if="player.ban.locked" :title="t('players.show.ban_locked')"></i>
                        {{ player.ban.timestamp | formatTime }}
                    </div>
                </div>

                <p class="text-gray-100">
                    <span class="whitespace-pre-line">{{ player.ban.reason || t('players.show.no_reason') }}</span>
                </p>

                <div class="flex justify-between">
                    <p class="text-sm italic monospace">{{ player.ban.banHash }}</p>
                    <p class="text-sm monospace font-semibold" v-if="player.ban.smurfAccount" :title="t('players.show.original_ban')">
                        <a :href="'/smurf/' + player.ban.smurfAccount" target="_blank" class="text-white hover:text-gray-800">{{ player.ban.smurfAccount }}</a>
                    </p>
                </div>

                <div class="mt-4 text-sm pt-1 border-t border-dashed" v-if="player.ban.info">
                    <b class="whitespace-nowrap">{{ player.ban.original }}:</b> <i>{{ player.ban.info }}</i>
                </div>

                <div class="mt-2 text-sm" v-if="player.ban.accuracy && !confirmedAccuracy" :title="t('players.show.accuracy_title', player.ban.accuracy.banned, player.ban.accuracy.total)">
                    <b class="whitespace-nowrap">{{ t('players.show.accuracy') }}:</b> <i>~{{ player.ban.accuracy.accuracy }}%</i>
                </div>
            </alert>

            <alert class="bg-rose-500" v-if="hwidBan">
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

            <!-- Issuing -->
            <div class="p-8 mb-10 bg-gray-100 rounded dark:bg-dark-secondary" v-if="isBanning">
                <template v-if="isBanLoading">
                    <img src="/images/banned.webp" class="h-72" style="image-rendering: pixelated" />
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
                                    <input class="block p-3 bg-gray-200 dark:bg-gray-600 rounded shadow mr-1" type="date" id="expireDate" name="expireDate" step="any" :min="$moment().format('YYYY-MM-DD')" v-model="form.ban.expireDate" required>
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
                            <textarea class="block w-full p-5 bg-gray-200 dark:bg-gray-600 rounded shadow" id="reason" name="reason" rows="5" :placeholder="player.playerName + ' did a big oopsie.'" v-model="form.ban.reason"></textarea>
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
                <inertia-link class="flex-1 block p-5 m-2 font-semibold text-white bg-indigo-600 rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" :href="'/logs?identifier=' + player.licenseIdentifier">
                    <i class="mr-1 fas fa-toilet-paper"></i>
                    {{ t('players.show.logs') }}
                </inertia-link>
                <a class="flex-1 block p-5 m-2 font-semibold text-white bg-steam rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" target="_blank" :href="player.steamProfileUrl" v-if="player.steamProfileUrl">
                    <i class="mr-1 fab fa-steam"></i>
                    {{ t('players.show.steam') }}
                </a>
                <button class="flex-1 block p-5 m-2 font-semibold text-white bg-rose-700 rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" @click="showAntiCheat" v-if="canSeeAntiCheat">
                    <i class="mr-1 fas fa-bullseye"></i>
                    <span>
                        {{ t('players.show.anti_cheat') }}
                    </span>
                </button>
            </div>
            <div class="flex flex-wrap items-center text-center">
                <a class="flex-1 block p-5 m-2 font-semibold text-white bg-discord rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" v-if="player.discord.length > 0" href="#" @click="showDiscord($event)">
                    <i class="mr-1 fab fa-discord"></i>
                    {{ t('players.show.discord_accounts', player.discord.length) }}
                </a>

                <button class="flex-1 block p-5 m-2 font-semibold text-white bg-indigo-600 rounded mobile:w-full mobile:m-0 mobile:mb-3 mobile:flex-none" @click="showLinked">
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
                <div class="grid grid-cols-1 xl:grid-cols-2 3xl:grid-cols-3 wide:grid-cols-4 gap-9">
                    <card v-for="(character) in characters" :key="character.id" v-bind:deleted="character.characterDeleted" class="relative mb-0" :class="{ 'shadow-lg': status && status.character === character.id }">
                        <template #header>
                            <div class="flex justify-between">
                                <div class="flex-shrink-0">
                                    <img class="w-32 h-32 rounded-3xl" src="/images/loading.svg" :data-lazy="character.mugshot" v-if="character.mugshot" />
                                    <img class="w-32 h-32 rounded-3xl" src="/images/no_mugshot.png" v-else :title="t('players.characters.no_mugshot')" />
                                </div>
                                <div class="w-full">
                                    <h3 class="mb-2">
                                        {{ character.name }} (#{{ character.id }})
                                    </h3>
                                    <h4 class="text-primary dark:text-dark-primary" :title="t('players.characters.created', $moment(character.characterCreationTimestamp).format('l'))">
                                        {{ t('players.characters.born') }} {{ $moment(character.dateOfBirth).format('l') }}
                                    </h4>
                                    <h4 class="text-red-700 dark:text-red-300" v-if="character.characterDeleted">
                                        {{ t('players.edit.deleted') }} {{ $moment(character.characterDeletionTimestamp).format('l') }}
                                    </h4>
                                    <h4 class="text-gray-700 dark:text-gray-300 text-sm italic font-mono mt-1">
                                        {{ pedModel(character.pedModelHash) }}
                                        <span v-if="character.creationTime" :title="t('players.new.creation_time')">
                                            ({{ formatSecondDiff(character.creationTime) }})
                                        </span>
                                    </h4>
                                    <h4 class="text-gray-700 dark:text-gray-300 text-xs italic font-mono mt-1" v-if="character.playtime" :title="t('players.characters.playtime')">
                                        {{ formatSecondDiff(character.playtime) }}
                                    </h4>
                                    <h4 class="text-gray-700 dark:text-gray-300 text-xs italic font-mono mt-1" v-if="character.last_loaded">
                                        {{ t('players.characters.last_loaded') }} {{ formatSecondDiff(Math.floor(Date.now() / 1000) - character.last_loaded) }}
                                    </h4>
                                </div>
                            </div>
                        </template>

                        <template>
                            <div class="max-h-72 overflow-y-auto text-sm leading-5 italic">
                                <p class="break-words">
                                    {{ character.backstory }}
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

                                    <inertia-link class="block w-full px-3 py-2 text-center text-white bg-blue-600 dark:bg-blue-400 rounded" :class="{ '2xl:w-split': status && status.character === character.id }" :href="'/inventories/character/' + character.id">
                                        <i class="fas fa-briefcase mr-1"></i>
                                        {{ t('inventories.view') }}
                                    </inertia-link>
                                </div>

                                <div class="flex justify-between gap-2 w-full mt-2">
                                    <button class="block w-full px-3 py-2 text-center text-white bg-warning dark:bg-dark-warning rounded" v-if="status && status.character === character.id" @click="form.unload.character = character.id; isUnloading = true">
                                        <i class="fas fa-bolt mr-1"></i>
                                        {{ t('players.show.unload') }}
                                    </button>

                                    <inertia-link class="block w-full px-3 py-2 text-center text-white bg-red-600 dark:bg-red-400 rounded" href="#" @click="deleteCharacter($event, character.id)" v-if="!character.characterDeleted && $page.auth.player.isSuperAdmin">
                                        <i class="fas fa-trash-alt mr-1"></i>
                                        {{ t('players.characters.delete') }}
                                    </inertia-link>
                                </div>

                                <!-- Small icon buttons -->
                                <div class="absolute top-1 left-1 right-1 flex gap-2 justify-between">
                                    <!-- Top left -->
                                    <div class="flex gap-2">
                                        <!-- Show inventory -->
                                        <inertia-link class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-blue-300 bg-secondary dark:bg-dark-secondary border-2 block" :href="'/inventory/character-' + character.id + ':1'" :title="t('inventories.show_inv')">
                                            <i class="fas fa-box"></i>
                                        </inertia-link>
                                    </div>

                                    <!-- Top right -->
                                    <div class="flex gap-2 justify-end">
                                        <!-- Character loaded -->
                                        <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-green-300 bg-secondary dark:bg-dark-secondary border-2 block cursor-help" :title="t('players.characters.loaded')" v-if="status && status.character === character.id">
                                            <i class="fas fa-plug"></i>
                                        </button>

                                        <!-- Character dead -->
                                        <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-red-300 bg-secondary dark:bg-dark-secondary border-2 block cursor-help" v-if="character.isDead" :class="{ 'left-10': status && status.character === character.id }">
                                            <i class="fas fa-skull-crossbones"></i>
                                        </button>

                                        <!-- Gender -->
                                        <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-pink-300 bg-secondary dark:bg-dark-secondary border-2 block cursor-help" v-if="character.gender === 1" :title="t('players.characters.is_female')">
                                            <i class="fas fa-female"></i>
                                        </button>
                                        <button class="p-1 text-sm font-bold leading-4 text-center w-7 rounded border-blue-300 bg-secondary dark:bg-dark-secondary border-2 block cursor-help" v-if="character.gender === 0" :title="t('players.characters.is_male')">
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
                    <template v-if="isAutomatedWarning(warning.message)">
                        <div v-if="showSystemWarnings" class="flex flex-col px-8 mb-5 bg-white dark:bg-gray-600 rounded-lg shadow-sm relative opacity-50 hover:opacity-100" :class="{ 'mb-3': index + 1 < warnings.length && isAutomatedWarning(warnings[index + 1].message) }">
                            <header class="text-center">
                                <div class="flex justify-between gap-4">
                                    <div class="flex justify-between gap-4">
                                        <div class="flex items-center py-4 pr-4 border-r border-gray-200 dark:border-gray-400 w-32 flex-shrink-0">
                                            <h4 class="truncate" v-if="warning.issuer.playerName === null">{{ t('global.system') }}</h4>
                                            <h4 class="truncate" :title="warning.issuer.playerName">
                                                <a :href="'/players/' + warning.issuer.licenseIdentifier" >{{ warning.issuer.playerName }}</a>
                                            </h4>
                                        </div>

                                        <div class="flex items-center py-4">
                                            <span class="italic text-xs text-muted dark:text-dark-muted text-left">{{ warning.message }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end py-4 pl-4 border-l border-gray-200 dark:border-gray-400 gap-3">
                                        <span class="italic text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap block w-36 text-right">
                                            {{ warning.createdAt | formatTime }}
                                        </span>

                                        <button class="block px-3 py-1 text-sm font-semibold text-white bg-red-500 rounded hover:bg-red-600" @click="deleteWarning(warning.id)" v-bind:href="'/players/' + player.licenseIdentifier + '/warnings/' + warning.id" v-if="warning.canDelete || $page.auth.player.isSeniorStaff">
                                            <i class="fas fa-trash"></i>
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
                                    <button class="px-3 py-1 ml-4 text-sm font-semibold text-white bg-yellow-500 rounded" @click="warningEditId = warning.id" v-if="warningEditId !== warning.id && $page.auth.player.licenseIdentifier === warning.issuer.licenseIdentifier && warning.warningType !== 'system'">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="px-3 py-1 ml-4 text-sm font-semibold text-white bg-success dark:bg-dark-success rounded" @click="editWarning(warning.id, warning.warningType)" v-if="warningEditId === warning.id">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <button class="px-3 py-1 ml-4 text-sm font-semibold text-white bg-muted dark:bg-dark-muted rounded" @click="warningEditId = 0" v-if="warningEditId === warning.id">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <inertia-link class="px-3 py-1 ml-4 text-sm font-semibold text-white bg-red-500 rounded hover:bg-red-600" method="DELETE" v-bind:href="'/players/' + player.licenseIdentifier + '/warnings/' + warning.id" v-if="warning.canDelete || $page.auth.player.isSeniorStaff">
                                        <i class="fas fa-trash"></i>
                                    </inertia-link>
                                </div>
                            </div>
                        </template>

                        <template>
                            <p class="text-muted dark:text-dark-muted" v-if="warningEditId !== warning.id">
                                <span class="whitespace-pre-wrap" v-html="markdown(formatWarning(warning.message), false)"></span>
                            </p>

                            <textarea class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-700" rows="8" :id="'warning_' + warning.id" v-else-if="warningEditId === warning.id">{{ warning.message }}</textarea>
                        </template>
                    </card>
                </template>

                <p class="text-muted dark:text-dark-muted" v-if="warnings.length === 0">
                    {{ t('players.show.no_warnings') }}
                </p>
            </template>

            <template #footer>
                <h3>
                    {{ t('players.warning.give') }}
                </h3>
                <form @submit.prevent="submitWarning">
                    <label for="message" class="mb-3 italic text-gray-800 dark:text-gray-200 block text-sm" v-html="t('players.warning.remember')"></label>

                    <div class="relative">
                        <inertia-link class="text-black dark:text-white no-underline absolute top-0.5 right-1.5" :title="t('global.support_markdown')" href="/docs/markdown">
                            <i class="fab fa-markdown"></i>
                        </inertia-link>

                        <textarea class="w-full p-5 mb-5 bg-gray-200 rounded shadow dark:bg-gray-600" id="message" name="message" rows="4" :placeholder="t('players.warning.placeholder', player.playerName)" v-model="form.warning.message" @input="warningMessageChanged()" required></textarea>
                    </div>

                    <button class="px-5 py-2 font-semibold text-white bg-red-500 dark:bg-red-500 rounded" @click="form.warning.warning_type = 'strike'" type="submit">
                        <i class="mr-1 fas fa-bolt"></i>
                        {{ t('players.warning.do_strike') }}
                    </button>
                    <button class="px-5 py-2 ml-2 font-semibold text-white bg-yellow-600 rounded" @click="form.warning.warning_type = 'warning'" type="submit">
                        <i class="mr-1 fas fa-exclamation-triangle"></i>
                        {{ t('players.warning.do_warn') }}
                    </button>
                    <button class="px-5 py-2 ml-2 font-semibold text-white bg-yellow-400 dark:bg-yellow-500 rounded" @click="form.warning.warning_type = 'note'" type="submit">
                        <i class="mr-1 fas fa-sticky-note"></i>
                        {{ t('players.warning.do_note') }}
                    </button>
                    <button class="px-5 py-2 ml-2 font-semibold text-white bg-pink-400 dark:bg-pink-500 rounded" @click="form.warning.warning_type = 'hidden'" type="submit" v-if="$page.auth.player.isSeniorStaff">
                        <i class="mr-1 fas fa-eye-slash"></i>
                        {{ t('players.warning.do_hidden_note') }}
                    </button>
                </form>
            </template>
        </v-section>

        <v-section :noFooter="true" :collapsed="extraDataCollapsed">
            <template #header>
                <div class="flex gap-4">
                    <div class="cursor-pointer text-2xl flex items-center" @click="extraDataCollapsed = !extraDataCollapsed">
                        <i class="fas fa-chevron-right" :class="{ 'rotate-90': !extraDataCollapsed }"></i>
                    </div>

                    <h2>
                        {{ t('players.show.logs_and_screenshots') }}
                    </h2>
                </div>
            </template>

            <template v-if="loadingExtraData">
                <div class="flex justify-center text-lg items-center">
                    <div>
                        <i class="fas fa-cog animate-spin"></i>
                        {{ t('global.loading') }}
                    </div>
                </div>
            </template>

            <template v-else>
                <table class="w-full">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="py-2 px-4">{{ t('screenshot.screenshot') }}</th>
                        <th class="py-2 px-4">{{ t('screenshot.note') }}</th>
                        <th class="py-2 px-4">{{ t('screenshot.created_at') }}</th>
                    </tr>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 mobile:border-b-4" v-for="(screenshot, index) in sortedScreenshots" :key="index">
                        <td class="py-2 px-4 border-t mobile:block" v-if="screenshot.system">
                            <a :href="screenshot.url" target="_blank" class="text-indigo-600 dark:text-indigo-400">{{ t('screenshot.view', screenshot.url.split(".").pop()) }}</a>
                        </td>
                        <td class="py-2 px-4 border-t mobile:block" v-else>
                            <a :href="'/export/screenshot/' + screenshot.filename" target="_blank" class="text-indigo-600 dark:text-indigo-400">{{ t('screenshot.view', screenshot.filename.split(".").pop()) }}</a>
                        </td>
                        <td class="py-2 px-4 border-t mobile:block">
                            <i class="fas fa-cogs mr-1" v-if="screenshot.system"></i>
                            {{ screenshot.note || 'N/A' }}
                            <a :href="cheatDocs(screenshot.note)" target="_blank" v-if="cheatDocs(screenshot.note)" class="text-yellow-600 dark:text-yellow-400 font-semibold" :title="t('screenshot.documentation')">?</a>
                        </td>
                        <td class="py-2 px-4 border-t w-60 mobile:block" v-if="screenshot.created_at">
                            {{ screenshot.created_at * 1000 | formatTime(true) }}
                        </td>
                        <td class="py-2 px-4 border-t mobile:block" v-else>{{ t('global.unknown') }}</td>
                    </tr>
                    <tr v-if="sortedScreenshots.length === 0">
                        <td class="py-2 px-4 text-center border-t" colspan="100%">
                            {{ t('screenshot.no_screenshots') }}
                        </td>
                    </tr>
                </table>

                <table class="w-full mt-6">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="py-2 px-4">{{ t('logs.action') }}</th>
                        <th class="py-2 px-4">{{ t('logs.details') }}</th>
                        <th class="py-2 px-4">{{ t('logs.timestamp') }}</th>
                    </tr>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 mobile:border-b-4" v-for="log in panelLogs" :key="log.id">
                        <td class="py-2 px-4 border-t mobile:block whitespace-nowrap">{{ log.action }}</td>
                        <td class="py-2 px-4 border-t mobile:block" v-html="formatPanelLog(log.log)"></td>
                        <td class="py-2 px-4 border-t w-60 mobile:block whitespace-nowrap">{{ log.timestamp | formatTime(true) }}</td>
                    </tr>
                    <tr v-if="panelLogs.length === 0">
                        <td class="py-2 px-4 text-center border-t" colspan="100%">
                            {{ t('players.show.no_panel_logs') }}
                        </td>
                    </tr>
                </table>
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
                        <img :src="screenshotImage" alt="Screenshot" class="w-full" />
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
                    <button class="px-5 py-2 rounded bg-warning dark:bg-dark-warning mr-2" @click="isAttachingScreenshot = true" v-if="screenshotImage && screenshotLicense">
                        {{ t('screenshot.attach') }}
                    </button>

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

        <ScreenshotAttacher :close="screenshotAttached" :license="screenshotLicense" :url="screenshotImage" v-if="isAttachingScreenshot" />
    </div>
</template>

<script>
import CountryFlag from 'vue-country-flag';

import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Badge from './../../Components/Badge';
import Alert from './../../Components/Alert';
import Card from './../../Components/Card';
import Avatar from './../../Components/Avatar';
import ScreenshotAttacher from './../../Components/ScreenshotAttacher';
import Modal from './../../Components/Modal';
import MetadataViewer from './../../Components/MetadataViewer';
import StatisticsTable from './../../Components/StatisticsTable';

import models from "../../data/ped_models.json";

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
        Alert,
        Card,
        Avatar,
        ScreenshotAttacher,
        Modal,
        MetadataViewer,
        StatisticsTable,
        CountryFlag
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
        tags: {
            type: Array,
            required: true,
        },
        enablableCommands: {
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
        },
        allowRoleEdit: {
            type: Boolean
        }
    },
    data() {
        let selectedRole;

        if (this.player.isSeniorStaff) {
            selectedRole = 'seniorStaff';
        } else if (this.player.isStaff) {
            selectedRole = 'staff';
        } else if (this.player.isTrusted) {
            selectedRole = 'trusted';
        } else {
            selectedRole = 'player';
        }

        const commands = this.enablableCommands.sort().map(c => {
            return {
                name: c,
                enabled: this.player.enabledCommands.includes(c),
            };
        });

        const autoExpandCollapsed = this.setting('expandCollapsed'),
            showSystemNotes = this.setting('showSystemNotes');

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
            form: {
                ban: {
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
                    message: this.t('players.show.unload_default'),
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

            commands: commands,
            isEnablingCommands: false,

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

            isRoleEdit: false,
            selectedRole: selectedRole,

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
            isAttachingScreenshot: false,

            screenCaptureLogs: null,

            loadingExtraData: false,
            loadingHWIDLink: false,

            sortedScreenshots: [],
            panelLogs: [],

            statusLoading: true,
            status: false,

            echo: false,
            showingEchoInfo: false,

            // Not actually opfw bans but rather bans on other subdivisions.
            globalBans: [],
            showingGlobalBans: false,

            playerTime: false,

            charactersCollapsed: false,
            warningsCollapsed: true && !autoExpandCollapsed,
            extraDataCollapsed: true && !autoExpandCollapsed,

            hwidBan: null,

            isLoading: false,
            showingMoreInfo: false,

            showingUserVariables: false,

            isShowingStaffStatistics: false,
            staffStatistics: {}
        }
    },
    computed: {
        confirmedAccuracy() {
            const ban = this.player.ban;

            return ban && ban.info && ban.info.startsWith('Impossible');
        },
        prettyHighAccuracy() {
            const ban = this.player.ban;

            return ban && ban.info && ban.info.startsWith('Highly unlikely');
        },
        minDate() {
            const date = new Date();

            date.setDate(date.getDate() + 1);

            return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
        },
        scheduledUnban() {
            if (!this.player.ban || !this.player.ban.scheduled) {
                return false;
            }

            return this.$moment.utc(this.player.ban.scheduled * 1000).format('MM/DD/YYYY - H:mm A');
        },
        scheduledUnbanIn() {
            if (!this.player.ban || !this.player.ban.scheduled) {
                return false;
            }

            return this.$moment.utc(this.player.ban.scheduled * 1000).fromNow();
        },
        systemNoteCount() {
            return this.warnings.filter(warn => this.isAutomatedWarning(warn.message)).length;
        },
        deletedCharacterCount() {
            return this.characters.filter(c => c.characterDeleted).length;
        },
    },
    methods: {
        warningMessageChanged() {
            const message = this.form.warning.message;

            sessionStorage.setItem(`warning_${this.player.licenseIdentifier}`, message);
        },
        resolveStaffStatistics(pSource) {
            return axios.get('/players/' + this.player.licenseIdentifier + '/statistics/' + pSource);
        },
        showGlobalBans() {
            this.showingGlobalBans = true;
        },
        updatePlayerTime() {
            const timezoneOffset = this.player.variables?.timezoneOffset;

            if (typeof timezoneOffset !== "number") {
                return;
            }

            this.playerTime = this.$moment().utcOffset(timezoneOffset * -1).format('h:mm:ss A');
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
        formatSecondDiff(sec) {
            return this.$moment.duration(sec, 'seconds').format('d[d] h[h] m[m] s[s]');
        },
        formatPanelLog(log) {
            return log.replace(/(license:\w+)(?=\))/gm, match => {
                const start = match.substring(8, 12),
                    end = match.substring(match.length - 4);

                return `<span class="text-gray-700 dark:text-gray-300" title="${match}">${start}...${end}</span>`;
            });
        },
        showAntiCheatMetadata(event, eventData) {
            event.preventDefault();

            this.antiCheatMetadata = true;

            this.antiCheatMetadataImage = eventData.screenshot_url;
            this.antiCheatMetadataJSON = eventData.metadata;
        },
        getPlayerMetadata() {
            const statusMetadata = this.status?.metadata;

            if (!statusMetadata) {
                return false;
            }

            const metadata = Object.keys(statusMetadata).map(key => {
                return statusMetadata[key] ? this.t("players.show.meta_" + key) : false;
            }).filter(Boolean);

            return metadata.length ? metadata.join(', ') : false;
        },
        async unbanPlayer() {
            if (this.isLoading) return;

            if (!this.player.ban.issuer && !this.isConfirmingUnban && (this.confirmedAccuracy || this.prettyHighAccuracy)) {
                this.isConfirmingUnban = true;
                this.confirmingUnbanInput = "";

                return;
            }

            this.isConfirmingUnban = false;

            this.isLoading = true;

            // Send request.
            await this.$inertia.delete('/players/' + this.player.licenseIdentifier + '/bans/' + this.player.ban.id);

            this.isLoading = false;
        },
        async scheduleUnban() {
            if (this.isLoading || !this.scheduledUnbanDate) return;

            const timestamp = this.$moment.utc(this.scheduledUnbanDate).unix();

            if (timestamp * 1000 < Date.now()) return;

            this.isLoading = true;
            this.isSchedulingUnban = false;

            // Send request.
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/bans/' + this.player.ban.id + '/schedule', {
                timestamp: timestamp
            });

            this.isLoading = false;
        },
        async updateCommands() {
            if (this.isLoading) return;

            this.isEnablingCommands = false;

            const enabledCommands = this.commands.filter(c => c.enabled).map(c => c.name);

            this.isLoading = true;

            // Send request.
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/updateEnabledCommands', {
                enabledCommands: enabledCommands,
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
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/unlink_hwid/' + this.hwidBan.license);

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
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/unlink/' + pLicenseIdentifier);

            this.isLoading = false;
        },
        cheatDocs(pNote) {
            pNote = pNote.trim();

            if (pNote.startsWith('Anti-Cheat: Invalid Damage Modifier')) {
                return '/docs/damage_modifier';
            }

            return false;
        },
        async loadExtraData() {
            this.loadingExtraData = true;

            try {
                const response = await axios.get('/players/' + this.player.licenseIdentifier + '/data');

                if (response.data && response.data.status) {
                    const data = response.data.data;

                    this.panelLogs = data.panelLogs;
                    this.sortedScreenshots = data.screenshots;

                    this.sortedScreenshots.sort((a, b) => b.created_at - a.created_at);
                }
            } catch (e) {
            }

            this.loadingExtraData = false;
        },
        showEchoInfo() {
            if (!this.echo || !this.echo.raw) return;

            this.showingEchoInfo = true;

            // Usernames:
            // https://steamcommunity.com/actions/ajaxresolveusers?steamids=76561198306882980
        },
        getEchoColor(info) {
            // Modal colors
            // border-gray-500 bg-gray-100 dark:bg-gray-950
            // border-teal-500 bg-teal-100 dark:bg-teal-950
            // border-green-500 bg-green-100 dark:bg-green-950
            // border-yellow-500 bg-yellow-100 dark:bg-yellow-950
            // border-red-500 bg-red-100 dark:bg-red-950

            // Badge colors
            // border-gray-300 bg-gray-200 dark:bg-gray-700
            // border-teal-300 bg-teal-200 dark:bg-teal-700
            // border-green-300 bg-green-200 dark:bg-green-700
            // border-yellow-300 bg-yellow-200 dark:bg-yellow-700
            // border-red-300 bg-gray-200 dark:bg-red-700

            const total = info.detected + info.unusual + info.clean;

            if (total > 0 && info.lastScanned !== false) {
                if (info.detected > 0) {
                    return "red";
                } else if (info.unusual > 0) {
                    return "yellow";
                } else if (info.clean > 0) {
                    return "green";
                }
            }

            return "teal";
        },
        getEchoIcon(info) {
            const total = info.detected + info.unusual + info.clean;

            if (total > 0 && info.lastScanned !== false) {
                if (info.detected > 0) {
                    return "fas fa-skull-crossbones";
                } else if (info.unusual > 0) {
                    return "fas fa-exclamation-triangle";
                } else if (info.clean > 0) {
                    return "fas fa-check";
                }
            }

            return "fas fa-user-slash";
        },
        async loadEchoStatus() {
            if (!this.$page.auth.player.isSeniorStaff) return;

            const echo = this.$page.echo;

            if (!echo) return;

            const steam = this.player.steam.map(s => {
                return {
                    raw: s,
                    int: BigInt(`0x${s.split(':').pop()}`)
                };
            });

            if (steam.length === 0) {
                this.echo = {
                    color: "gray",
                    icon: "fas fa-heart-broken",
                    title: this.t('players.show.echo_failed_steam')
                };

                return;
            }

            this.echo = {
                color: "gray",
                icon: "fas fa-spinner animate-spin",
                title: this.t('global.loading')
            };

            const resolve = async steam => {
                let error;

                try {
                    const url = echo.replace(/\/?$/, '/') + steam.int;

                    const response = await axios.get(url);

                    if (response.data && response.data.game === "fivem") {
                        const data = response.data;

                        data.identifier = steam.raw;
                        data.steam = steam.int;

                        data.lastScanned = this.$moment(data.lastScanned);

                        if (data.lastScanned.unix() <= 0) {
                            data.lastScanned = false;
                        }

                        data.color = this.getEchoColor(data);
                        data.icon = this.getEchoIcon(data);

                        return data;
                    }
                } catch (e) {
                    error = e.message;
                }

                return {
                    identifier: steam.raw,
                    steam: steam.int,
                    color: "gray",
                    icon: "fas fa-question",
                    failed: error || "Unknown error"
                };
            };

            const data = (await Promise.all(steam.map(resolve))),
                joined = data
                    .filter(d => !d.failed)
                    .reduce((a, b) => {
                        a.detected += b.detected;
                        a.unusual += b.unusual;
                        a.clean += b.clean;

                        a.total++;

                        return a;
                    }, {
                        detected: 0,
                        unusual: 0,
                        clean: 0,
                        total: 0
                    });

            if (joined && joined.total > 0) {
                joined.title = this.t('players.show.echo_title');

                joined.raw = data;

                joined.color = this.getEchoColor(joined);
                joined.icon = this.getEchoIcon(joined);

                this.echo = joined;
            } else {
                this.echo = {
                    color: "gray",
                    icon: "fas fa-question",
                    title: this.t('players.show.echo_failed'),
                    raw: data
                };
            }
        },
        async loadGlobalBans() {
            const global = this.$page.global;

            if (!global) return;

            try {
                const url = global.replace(/\/?$/, '/') + `bans/${this.player.licenseIdentifier}`;

                const response = await axios.get(url);

                if (response.data && Array.isArray(response.data)) {
                    this.globalBans = response.data;
                }
            } catch (e) {
            }
        },
        async loadStatus() {
            this.statusLoading = true;

            const status = (await this.requestData("/online/" + this.player.licenseIdentifier)) || {};

            this.status = status[this.player.licenseIdentifier] || false;

            this.statusLoading = false;
        },
        async loadHWIDLink() {
            if (this.player.ban) {
                return;
            }

            this.loadingHWIDLink = true;

            try {
                const response = await axios.get('/players/' + this.player.licenseIdentifier + '/linked_hwid');

                if (response.data && response.data.status) {
                    const data = response.data.data;

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
                        number = parseInt(number).toLocaleString("en-US");

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

            this.captureData.duration = parseInt(this.captureData.duration) || 0;

            if (!Number.isInteger(this.captureData.duration) || this.captureData.duration < 1 || this.captureData.duration > 30) {
                alert(this.t("screenshot.invalid_duration"));

                return;
            }

            let interval = setInterval(() => {
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
                const result = await axios({
                    method: 'post',
                    url: '/api/capture/' + this.$page.serverName + '/' + this.status.source + '/' + this.captureData.duration,
                    timeout: this.captureData.duration + 20000
                });

                clearInterval(interval);

                if (result.data) {
                    if (result.data.status) {
                        console.info('Screen capture of ID ' + this.status.source, result.data.data.url, result.data.data.license);

                        this.screenCaptureVideo = result.data.data.url;
                        this.screenCaptureLogs = this.formatScreenCaptureLogs(result.data.data.logs);
                    } else {
                        this.screenshotError = result.data.message ? result.data.message : this.t('screenshot.screencapture_failed');
                    }
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
                cb && cb(false);

                return;
            }
            this.isScreenshotLoading = true;
            this.screenshotError = null;

            this.screenCaptureLogs = null;
            this.screenshotLicense = null;
            this.screenshotFlags = null;

            try {
                const result = await axios.post('/api/screenshot/' + this.$page.serverName + '/' + this.status.source + (shortLifespan ? '?short=1' : ''));
                this.isScreenshotLoading = false;

                if (result.data) {
                    if (result.data.status) {
                        console.info('Screenshot of ID ' + this.status.source, result.data.data.url, result.data.data.license);

                        this.screenshotImage = result.data.data.url;
                        this.screenshotLicense = result.data.data.license;

                        this.screenshotFlags = result.data.data.flags;
                        this.screenCaptureLogs = this.formatScreenCaptureLogs(result.data.data.logs);

                        cb && cb(true);
                    } else {
                        this.screenshotError = result.data.message ? result.data.message : this.t('map.screenshot_failed');

                        cb && cb(false);
                    }
                }
            } catch (e) {
                console.error(e);

                this.screenshotError = this.t('map.screenshot_failed');

                this.isScreenshotLoading = false;

                cb && cb(false);
            }
        },
        screenshotAttached(status, message) {
            this.isAttachingScreenshot = false;

            if (message) {
                alert(message);
            }

            if (status) {
                this.isScreenshot = false;
                this.screenshotImage = null;
                this.screenshotTimings = null;
                this.screenshotError = null;
                this.screenshotLicense = null;
            }
        },
        async showDiscord(e) {
            e.preventDefault();
            this.isShowingDiscordLoading = true;
            this.isShowingDiscord = true;

            this.discordAccounts = [];

            try {
                const data = await axios.get('/players/' + this.player.licenseIdentifier + '/discord');

                if (data.data && data.data.status) {
                    const accounts = data.data.data;

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
                const data = await axios.get('/players/' + this.player.licenseIdentifier + '/linked');

                if (data.data && data.data.status) {
                    const linked = data.data.data;

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
                const data = await axios.get('/players/' + this.player.licenseIdentifier + '/antiCheat');

                if (data.data && data.data.status) {
                    this.antiCheatEvents = data.data.data;
                }
            } catch (e) { }

            this.isShowingAntiCheatLoading = false;
        },
        getWarningTypeIcon(type) {
            const label = this.t('players.show.warning_type.' + type);

            switch (type) {
                case 'strike':
                    return '<span class="cursor-help text-red-500"><i class="fas fa-bolt" title="' + label + '"></i></span>';
                case 'warning':
                    return '<span class="cursor-help text-yellow-600"><i class="fas fa-exclamation-triangle" title="' + label + '"></i></span>';
                case 'note':
                    return '<span class="cursor-help text-yellow-400"><i class="fas fa-sticky-note" title="' + label + '"></i></span>';
                case 'system':
                    return '<span class="cursor-help text-blue-500"><i class="fas fa-robot" title="' + label + '"></i></span>';
                case 'hidden':
                    return '<span class="cursor-help text-pink-500"><i class="fas fa-eye-slash" title="' + label + '"></i></span>';
            }

            return '';
        },
        pedModel(hash) {
            if (!hash) {
                return 'unknown';
            }

            return models[hash] || hash;
        },
        localizeBan() {
            if (!this.player.ban) {
                return '';
            }
            return this.player.ban.expireAt
                ? this.t('players.show.ban', this.formatBanCreator(this.player.ban.issuer), this.$options.filters.formatTime(this.player.ban.expireAt))
                : this.t('players.ban.forever', this.formatBanCreator(this.player.ban.issuer));
        },
        formatTime(t) {
            return this.$options.filters.formatTime(t);
        },
        async pmPlayer() {
            // Send request.
            await this.$inertia.post('/players/' + (this.player.overrideLicense ? this.player.overrideLicense : this.player.licenseIdentifier) + '/staffPM', this.form.pm);

            // Reset.
            this.isStaffPM = false;
            this.form.pm.message = null;
        },
        async removeSoftBan() {
            if (!confirm(this.t('players.show.soft_ban_confirm'))) {
                return;
            }

            // Send request.
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/updateSoftBanStatus/0');
        },
        async addSoftBan() {
            if (!confirm(this.t('players.show.soft_ban_confirm'))) {
                return;
            }

            // Send request.
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/updateSoftBanStatus/1');
        },
        async removeTag() {
            this.isTagging = false;

            // Send request.

            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/updateTag', {
                tag: false
            });
        },
        async updateRole() {
            this.isRoleEdit = false;

            // Send request.
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/updateRole', {
                role: this.selectedRole ? this.selectedRole : 'player'
            });
        },
        async addTag() {
            const tag = this.tagCategory === 'custom' ? this.tagCustom.trim() : this.tagCategory;

            if (!tag) {
                return;
            }

            this.isTagging = false;

            // Send request.

            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/updateTag', {
                tag: tag
            });
        },
        async kickPlayer() {
            if (!confirm(this.t('players.show.kick_confirm'))) {
                this.isKicking = false;
                return;
            }

            // Send request.
            await this.$inertia.post('/players/' + (this.player.overrideLicense ? this.player.overrideLicense : this.player.licenseIdentifier) + '/kick', this.form.kick);

            // Reset.
            this.isKicking = false;
            this.form.kick.reason = null;
        },
        async revivePlayer() {
            if (!confirm(this.t('players.show.revive_confirm'))) {
                return;
            }

            // Send request.
            await this.$inertia.post('/players/' + (this.player.overrideLicense ? this.player.overrideLicense : this.player.licenseIdentifier) + '/revivePlayer');
        },
        async unloadCharacter() {
            if (!confirm(this.t('players.show.unload_confirm'))) {
                return;
            }

            // Send request.
            await this.$inertia.post('/players/' + (this.player.overrideLicense ? this.player.overrideLicense : this.player.licenseIdentifier) + '/unloadCharacter', this.form.unload);

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
            await this.$inertia.delete('/players/' + this.player.licenseIdentifier + '/characters/' + characterId);
        },
        async submitBan() {
            // Default expiration.
            let expire = null;

            // Calculate expire relative to now in seconds if temp ban.
            if (this.isTempBanning) {
                const nowUnix = this.$moment().unix();

                if (this.isTempSelect) {
                    const expireUnix = this.$moment(this.form.ban.expireDate + ' ' + this.form.ban.expireTime).unix();
                    expire = expireUnix - nowUnix;
                } else {
                    let val = parseInt($('#ban-value').val());

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
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/bans', { ...this.form.ban, expire });

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
            await this.$inertia.post('/players/' + this.player.licenseIdentifier + '/warnings', this.form.warning);

            // Reset.
            this.form.warning.message = null;

            // Remove saved message.
            sessionStorage.removeItem(`warning_${this.player.licenseIdentifier}`);
        },
        async editWarning(id, warningType) {
            // Send request.
            await this.$inertia.put('/players/' + this.player.licenseIdentifier + '/warnings/' + id, {
                message: $('#warning_' + id).val(),
                warning_type: warningType,
            });

            // Reset.
            this.warningEditId = 0;
        },
        async deleteWarning(id) {
            if (!confirm(this.t('players.show.delete_warning'))) {
                return;
            }

            // Send request.
            await this.$inertia.delete('/players/' + this.player.licenseIdentifier + '/warnings/' + id);
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
            const _this = this,
                button = $(e.target).closest('.badge');

            this.copyToClipboard(this.player.licenseIdentifier);

            $('span', button).text(this.t('global.copied'));

            setTimeout(function () {
                $('span', button).text(_this.t('players.show.copy_license'));
            }, 1500);
        },
        copyText(e, text) {
            e.preventDefault();
            const button = $(e.target).closest('a');

            this.copyToClipboard(text)

            button.removeClass('bg-blue-800');
            button.addClass('bg-green-600');

            setTimeout(function () {
                button.removeClass('bg-green-600');
                button.addClass('bg-blue-800');
            }, 500);
        },
        isAutomatedWarning(warning) {
            return warning.includes('This warning was generated automatically') || warning.startsWith('I scheduled the removal of this players ban for') || warning.startsWith('I removed this players ban.');
        },
        formatWarning(warning) {
            warning = warning.replace(/(https?:\/\/(.+?)\/players\/)?(steam:\w{15})/gmi, (full, _ignore, host, steam) => {
                const url = full && full.startsWith("http") ? full : "/players/" + steam,
                    cluster = host ? host.split(".")[0].replace("localhost", "c1") : this.$page?.auth?.cluster;

                return `<a href="${url}" target="_blank" class="text-yellow-600 dark:text-yellow-400">${cluster.toLowerCase()}/${steam.toLowerCase()}</a>`;
            });

            warning = warning.replace(/(https?:\/\/(.+?)\/players\/)?(license:\w{40})/gmi, (full, _ignore, host, license) => {
                const url = full && full.startsWith("http") ? full : "/players/" + license,
                    cluster = host ? host.split(".")[0].replace("localhost", "c1") : this.$page?.auth?.cluster;

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
            this.form.warning.message = savedMessage;
        }

        // Delay loading of extra data since it blocks other resources from loading
        setTimeout(() => {
            this.loadExtraData();
            this.loadHWIDLink();
            this.loadStatus();
            this.loadGlobalBans();
            this.loadEchoStatus();
        }, 500);

        this.updatePlayerTime();

        setInterval(() => {
            this.updatePlayerTime();
        }, 1000);

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
