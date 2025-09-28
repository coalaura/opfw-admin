<template>
    <div>
        <portal to="title">
            <div class="flex items-start space-x-10 mobile:flex-wrap">
                <h1 class="dark:text-white">
                    {{ character.name }} #{{ character.id }}
                </h1>
                <div class="flex items-center space-x-5 mobile:flex-wrap mobile:w-full mobile:!mr-0 mobile:!ml-0 mobile:space-x-0">
                    <badge class="border-red-200 bg-danger-pale dark:bg-dark-danger-pale" v-if="character.characterDeleted">
                        <span class="font-semibold">
                            {{ t('players.edit.deleted') }}:
                            {{ character.characterDeletionTimestamp | formatTime }}
                        </span>
                    </badge>
                </div>
            </div>

            <div class="mt-2 italic text-sm font-mono text-gray-500 dark:text-gray-400">
                {{ pedModel(character.pedModelHash) }}
            </div>

            <div class="mt-0.5 italic text-sm font-mono text-gray-500 dark:text-gray-400" v-if="character.coords">
                <span v-if="isOffline">{{ character.coords.x.toFixed(1) }}, {{ character.coords.y.toFixed(1) }}, {{ character.coords.z.toFixed(1) }}</span>
                <span class="blur-xs font-semibold" :title="t('players.characters.no_coords')" v-else>123.4, -567.8, 901.2</span>
            </div>
        </portal>

        <portal to="actions">
            <div>
                <!-- Remove Tattoos -->
                <a href="#" class="px-5 py-2 font-semibold text-white rounded bg-danger mr-3 dark:bg-dark-danger mobile:block mobile:w-full mobile:m-0 mobile:mb-3" @click="(e) => { e.preventDefault(); isTattooRemoval = true }">
                    <i class="fas fa-eraser"></i>
                    {{ t('players.characters.remove_tattoo') }}
                </a>
                <!-- Reset Spawn-point -->
                <a href="#" class="px-5 py-2 font-semibold text-white rounded bg-warning mr-3 dark:bg-dark-warning mobile:block mobile:w-full mobile:m-0 mobile:mb-3" @click="(e) => { e.preventDefault(); isResetSpawn = true }">
                    <i class="fas fa-heartbeat"></i>
                    {{ t('players.characters.reset_spawn') }}
                </a>
                <!-- Back -->
                <a class="px-5 py-2 font-semibold text-white rounded bg-primary dark:bg-dark-primary mobile:block mobile:w-full mobile:m-0 mobile:mb-3" :href="'/players/' + character.licenseIdentifier">
                    <i class="fas fa-backward"></i>
                    {{ t('players.characters.to_player') }}
                </a>
            </div>
        </portal>

        <!-- Remove Tattoos -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-30" v-if="isTattooRemoval">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-4 rounded w-alert">
                <h3 class="mb-2">{{ t('players.characters.sure_tattoos') }}</h3>
                <div class="w-full p-3 flex justify-between">
                    <label class="mr-4 block w-1/4 text-center pt-2 font-bold">
                        {{ t('players.characters.tattoo_zone') }}
                    </label>
                    <select class="w-3/4 px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="zone">
                        <option value="all">{{ t('players.characters.zone.all') }}</option>
                        <option value="head" selected>{{ t('players.characters.zone.head') }}</option>
                        <option value="left_arm">{{ t('players.characters.zone.left_arm') }}</option>
                        <option value="right_arm">{{ t('players.characters.zone.right_arm') }}</option>
                        <option value="torso">{{ t('players.characters.zone.torso') }}</option>
                        <option value="left_leg">{{ t('players.characters.zone.left_leg') }}</option>
                        <option value="right_leg">{{ t('players.characters.zone.right_leg') }}</option>
                    </select>
                </div>
                <p v-html="t('players.characters.tattoo_no_undo')"></p>
                <div class="flex justify-end mt-2">
                    <button type="button" class="px-5 py-2 hover:shadow-xl font-semibold text-white rounded bg-dark-secondary mr-3 dark:text-black dark:bg-secondary" @click="isTattooRemoval = false">
                        {{ t('global.cancel') }}
                    </button>
                    <button type="button" class="px-5 py-2 hover:shadow-xl font-semibold text-white rounded bg-danger mr-3 dark:bg-dark-danger" @click="removeTattoos">
                        {{ t('players.characters.tattoo_do') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Reset spawn -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-30" v-if="isResetSpawn">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-4 rounded w-alert">
                <h3 class="mb-2">{{ t('players.characters.sure_spawn') }}</h3>
                <div class="w-full p-3 flex justify-between">
                    <label class="mr-4 block w-1/4 text-center pt-2 font-bold">
                        {{ t('players.characters.spawn_point') }}
                    </label>
                    <select class="w-3/4 px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="spawn">
                        <option v-for="coords in getResetCoords()" :key="coords.key" :value="coords.key">
                            {{ coords.label }}
                        </option>
                        <option value="staff" v-if="player.isStaff">
                            {{ t('players.characters.spawn.staff') }}
                        </option>
                    </select>
                </div>
                <p v-html="t('players.characters.spawn_no_undo')"></p>
                <div class="flex justify-end mt-2">
                    <button type="button" class="px-5 py-2 hover:shadow-xl font-semibold text-white rounded bg-dark-secondary mr-3 dark:text-black dark:bg-secondary" @click="isResetSpawn = false">
                        {{ t('global.cancel') }}
                    </button>
                    <button type="button" class="px-5 py-2 hover:shadow-xl font-semibold text-white rounded bg-danger mr-3 dark:bg-dark-danger" @click="resetSpawn">
                        {{ t('players.characters.spawn_do') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Editing -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('global.info') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent="submit(false)">
                    <!-- Name & Phone -->
                    <div class="flex flex-wrap mb-4">
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="first_name">
                                {{ t('players.edit.prename') }}
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="first_name" v-model="form.first_name">
                        </div>
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="last_name">
                                {{ t('players.edit.surname') }}
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="last_name" v-model="form.last_name">
                        </div>
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="dob">
                                {{ t('players.edit.dob') }}
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="dob" v-model="form.date_of_birth">
                        </div>
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2">
                                {{ t('players.edit.gender') }}
                            </label>
                            <select class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="gender" v-model="form.gender">
                                <option value="0">{{ t('global.male') }}</option>
                                <option value="1">{{ t('global.female') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-3 mb-6">
                        <label class="block mb-3">
                            {{ t('players.edit.backstory') }}
                        </label>
                        <textarea class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="backstory" v-model="form.backstory"></textarea>
                    </div>

                    <div class="px-3 my-6 flex justify-end" v-if="characterEdited">
                        <button class="px-5 py-3 font-semibold text-center text-white bg-green-600 rounded dark:bg-green-500 w-1/4" type="submit">
                            {{ t('players.characters.save_changes') }}
                        </button>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-600">

                    <div class="flex flex-wrap mb-6 mt-6">
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
                            <label class="block font-semibold">
                                {{ t('players.characters.license.licenses') }}
                            </label>
                            <ul v-if="character.licenses.length > 0" class="text-sm">
                                <li v-for="license in character.licenses" :key="license" class="ml-3 pl-2 list-dash">
                                    {{ t('players.characters.license.' + license) }}
                                </li>
                            </ul>
                            <ul v-else class="text-sm">
                                <li class="ml-3 pl-2 list-dash">{{ t('global.none') }}</li>
                            </ul>

                            <!-- Add License -->
                            <button type="button" class="block w-full px-5 py-2 mt-6 hover:shadow-xl font-semibold text-white rounded bg-primary mr-3 dark:bg-dark-primary" @click="isLicenseEdit = true">
                                {{ t('players.characters.license.add') }}
                            </button>
                        </div>
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
                            <table class="text-left w-full">
                                <tr>
                                    <th class="font-semibold p-2">{{ t('players.edit.phone') }}</th>
                                    <td class="p-2">
                                        <span class="block border-gray-500 border-b-2 px-3 py-2">
                                            {{ character.phoneNumber }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="font-semibold p-2">{{ t('players.edit.email') }}</th>
                                    <td class="p-2">
                                        <span class="flex justify-between items-center border-gray-500 border-b-2 px-3 py-2 relative">
                                            {{ character.emailAddress || "N/A" }}

                                            <i class="fas fa-sync cursor-pointer" :class="{ 'animate-spin': isRefreshingEmail }" @click="refreshEmail"></i>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="italic p-2 font-normal" colspan="2">
                                        {{ t('players.characters.has_outfits', character.outfits.length) }}
                                        <span class="cursor-pointer dark:text-blue-300 text-blue-500" @click="viewOutfits()" v-if="$page.auth.player.isSeniorStaff && character.outfits.length > 0">{{ t('global.view') }}</span>
                                    </th>
                                </tr>
                            </table>
                        </div>
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3 relative">
                            <table class="text-left w-full">
                                <tr>
                                    <th class="p-2">
                                        <label class="block font-semibold">
                                            {{ t('players.characters.edit_cash') }}
                                        </label>
                                    </th>
                                    <td class="p-2 relative">
                                        <template v-if="$page.auth.player.isSuperAdmin">
                                            <input type="number" class="block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" v-model="balanceForm.cash" />
                                            <span class="absolute top-0 left-0 font-mono text-xs leading-1 italic text-gray-500 dark:text-gray-400 pointer-events-none" v-if="balanceForm.cash !== 0">{{ numberFormat(balanceForm.cash, 0, true) }}</span>
                                        </template>

                                        <span class="block border-gray-500 border-b-2 px-3 py-2" v-else>{{ numberFormat(balanceForm.cash, 0, true) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="p-2">
                                        <label class="block font-semibold">
                                            {{ t('players.characters.edit_bank') }}
                                        </label>
                                    </th>
                                    <td class="p-2 relative">
                                        <template v-if="$page.auth.player.isSuperAdmin">
                                            <input type="number" class="block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" v-model="balanceForm.bank" />
                                            <span class="absolute top-0 left-0 font-mono text-xs leading-1 italic text-gray-500 dark:text-gray-400 pointer-events-none" v-if="balanceForm.bank !== 0">{{ numberFormat(balanceForm.bank, 0, true) }}</span>
                                        </template>

                                        <span class="block border-gray-500 border-b-2 px-3 py-2" v-else>{{ numberFormat(balanceForm.bank, 0, true) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="p-2">
                                        <label class="block font-semibold">
                                            {{ t('players.characters.edit_stocks') }}
                                        </label>
                                    </th>
                                    <td class="p-2 relative">
                                        <template v-if="$page.auth.player.isSuperAdmin">
                                            <input type="number" class="block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" v-model="balanceForm.stocks" />
                                            <span class="absolute top-0 left-0 font-mono text-xs leading-1 italic text-gray-500 dark:text-gray-400 pointer-events-none" v-if="balanceForm.stocks !== 0">{{ numberFormat(balanceForm.stocks, 0, true) }}</span>
                                        </template>

                                        <span class="block border-gray-500 border-b-2 px-3 py-2" v-else>{{ numberFormat(balanceForm.stocks, 0, true) }}</span>
                                    </td>
                                </tr>
                                <tr v-if="$page.auth.player.isSuperAdmin && balanceEdited">
                                    <td class="p-2">&nbsp;</td>
                                    <td class="p-2">
                                        <button type="button" class="block w-full px-5 py-2 hover:shadow-xl font-semibold text-white rounded bg-green-600 mr-3 dark:bg-green-500" @click="editBalance">
                                            {{ t('players.characters.save_changes') }}
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </form>
            </template>
        </v-section>

        <!-- Job -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('players.job.job') }}
                </h2>
            </template>

            <template>
                <div class="flex flex-wrap mb-4">
                    <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                        <label class="block mb-3">
                            {{ t('players.job.name') }}
                        </label>
                        <select class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="job" v-model="form.job_name" @change="setPayCheck">
                            <option :value="false">Unemployed</option>

                            <option :value="job.name" v-for="job in formattedJobs">{{ job.name || t('global.none') }}</option>
                        </select>
                    </div>
                    <div class="w-1/4 px-3 mobile:w-full mobile:mb-3" v-if="form.job_name === job.name" v-for="job in formattedJobs">
                        <label class="block mb-3">
                            {{ t('players.job.department') }}
                        </label>
                        <select class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="department" v-model="form.department_name" @change="setPayCheck">
                            <option :value="false" v-if="!form.job_name">Unemployed</option>

                            <option :value="department.name" v-for="department in job.departments">
                                {{ department.name || t('global.none') }}
                            </option>
                        </select>
                    </div>
                    <template v-if="form.job_name === job.name" v-for="job in formattedJobs">
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3" v-if="form.department_name === department.name" v-for="department in job.departments">
                            <label class="block mb-3">
                                {{ t('players.job.position') }}
                            </label>
                            <select class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="position" v-model="form.position_name" @change="setPayCheck">
                                <option :value="false" v-if="!form.job_name">Unemployed</option>

                                <option :value="position" v-for="position in department.positions">
                                    {{ position || t('global.none') }}
                                </option>
                            </select>
                        </div>
                    </template>
                    <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                        <label class="block mb-3">&nbsp;</label>
                        <button class="block w-full px-4 py-3 mb-3 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" @click="updateJob">
                            {{ t('players.job.set') }} (${{ paycheck }})
                        </button>
                    </div>
                </div>
            </template>
        </v-section>

        <!-- Vehicle Editing -->
        <modal :show.sync="isVehicleEdit">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.characters.vehicle.edit') }}
                </h1>
            </template>

            <template #default>
                <table class="text-left w-full">
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.owner') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" min="0" v-model="vehicleForm.owner_cid" />
                        </td>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.plate') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" minlength="3" maxlength="8" v-model="vehicleForm.plate" />
                        </td>
                    </tr>
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.repair') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <select class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent dark:bg-gray-600" v-model="vehicleForm.repair">
                                <option value="fix">{{ t('players.characters.vehicle.repair_fix') }}</option>
                                <option value="break">{{ t('players.characters.vehicle.repair_break') }}</option>
                                <option :value="false">{{ t('players.characters.vehicle.repair_false') }}</option>
                            </select>
                        </td>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.fuel') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input type="number" class="w-28 block outline-none shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" min="0" max="100" step="0.1" v-model="vehicleForm.fuel" />
                        </td>
                    </tr>
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.is_supporter') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <select class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent dark:bg-gray-600" v-model="vehicleForm.supporter">
                                <option :value="1">{{ t('players.characters.vehicle.supporter_yes') }}</option>
                                <option :value="0">{{ t('players.characters.vehicle.supporter_no') }}</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div class="my-6 border-t-2 border-dashed border-gray-500"></div>

                <table class="text-left w-full">
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.neon_enabled') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <select class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent dark:bg-gray-600" v-model="vehicleForm.modifications.neon_enabled">
                                <option :value="true">{{ t('global.yes') }}</option>
                                <option :value="false">{{ t('global.no') }}</option>
                            </select>
                        </td>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.xenon_headlights') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <select class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent dark:bg-gray-600" v-model="vehicleForm.modifications.xenon_headlights">
                                <option :value="true">{{ t('global.yes') }}</option>
                                <option :value="false">{{ t('global.no') }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.tire_smoke') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input class="w-28 block outline-none shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" type="color" v-model="vehicleForm.modifications.tire_smoke" />
                        </td>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.neon') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input class="w-28 block outline-none shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" type="color" v-model="vehicleForm.modifications.neon" />
                        </td>
                    </tr>
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.turbo') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <select class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent dark:bg-gray-600" v-model="vehicleForm.modifications.turbo">
                                <option :value="true">{{ t('global.yes') }}</option>
                                <option :value="false">{{ t('global.no') }}</option>
                            </select>
                        </td>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.horn') }}
                                <sup>
                                    <a class="dark:text-blue-300 text-blue-500" href="https://gta.fandom.com/wiki/Los_Santos_Customs/Horns" :title="t('players.characters.vehicle.horn_title')" target="_blank">[?]</a>
                                </sup>
                            </label>
                        </th>
                        <td class="p-2">
                            <select class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent dark:bg-gray-600" v-model="vehicleForm.modifications.horn">
                                <optgroup :label="group" v-for="(hornList, group) in horns">
                                    <option :value="horn.index" v-for="horn in hornList">{{ horn.label }}</option>
                                </optgroup>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.engine') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input type="number" class="w-28 block outline-none shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" min="0" max="4" v-model="vehicleForm.modifications.engine" />
                        </td>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.transmission') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input type="number" class="w-28 block outline-none shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" min="0" max="3" v-model="vehicleForm.modifications.transmission" />
                        </td>
                    </tr>
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.breaks') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input type="number" class="w-28 block outline-none shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" min="0" max="3" v-model="vehicleForm.modifications.breaks" />
                        </td>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.suspension') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input type="number" class="w-28 block outline-none shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" min="0" max="4" v-model="vehicleForm.modifications.suspension" />
                        </td>
                    </tr>
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.armor') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <input type="number" class="w-28 block outline-none shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent" min="0" max="5" v-model="vehicleForm.modifications.armor" />
                        </td>
                    </tr>
                    <tr>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.tint') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <select class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent dark:bg-gray-600" v-model="vehicleForm.modifications.tint">
                                <option :value="0">{{ t('players.characters.vehicle.tints.0') }}</option>
                                <option :value="1">{{ t('players.characters.vehicle.tints.1') }}</option>
                                <option :value="2">{{ t('players.characters.vehicle.tints.2') }}</option>
                                <option :value="3">{{ t('players.characters.vehicle.tints.3') }}</option>
                                <option :value="4">{{ t('players.characters.vehicle.tints.4') }}</option>
                                <option :value="5">{{ t('players.characters.vehicle.tints.5') }}</option>
                            </select>
                        </td>
                        <th class="p-2">
                            <label class="block font-semibold">
                                {{ t('players.characters.vehicle.plate_type') }}
                            </label>
                        </th>
                        <td class="p-2">
                            <select class="w-28 block shadow-none !border-gray-500 border-0 border-b-2 bg-transparent !ring-transparent dark:bg-gray-600" v-model="vehicleForm.modifications.plate_type">
                                <option :value="0">{{ t('players.characters.vehicle.plates.0') }}</option>
                                <option :value="3">{{ t('players.characters.vehicle.plates.3') }}</option>
                                <option :value="4">{{ t('players.characters.vehicle.plates.4') }}</option>
                                <option :value="2">{{ t('players.characters.vehicle.plates.2') }}</option>
                                <option :value="1">{{ t('players.characters.vehicle.plates.1') }}</option>
                                <option :value="5">{{ t('players.characters.vehicle.plates.5') }}</option>
                                <option :value="6">{{ t('players.characters.vehicle.plates.6') }}</option>
                                <option :value="7">{{ t('players.characters.vehicle.plates.7') }}</option>
                                <option :value="8">{{ t('players.characters.vehicle.plates.8') }}</option>
                                <option :value="9">{{ t('players.characters.vehicle.plates.9') }}</option>
                                <option :value="10">{{ t('players.characters.vehicle.plates.10') }}</option>
                                <option :value="11">{{ t('players.characters.vehicle.plates.11') }}</option>
                                <option :value="12">{{ t('players.characters.vehicle.plates.12') }}</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isVehicleEdit = false">
                    {{ t('global.cancel') }}
                </button>

                <button type="button" class="px-5 py-2 rounded bg-green-100 hover:bg-green-200 dark:bg-green-600 dark:hover:bg-green-400" @click="editVehicle">
                    <span v-if="!isVehicleLoading">
                        {{ t('players.characters.vehicle.confirm') }}
                    </span>
                    <span v-else>
                        <i class="fas fa-cog animate-spin"></i>
                        {{ t('global.loading') }}
                    </span>
                </button>
            </template>
        </modal>

        <!-- Vehicle Adding -->
        <modal :show.sync="isVehicleAdd">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.characters.vehicle.add') }}
                </h1>
            </template>

            <template #default>
                <div class="flex gap-3 items-center">
                    <label class="block w-40" for="modelName">
                        {{ t('players.characters.vehicle.model') }}
                    </label>

                    <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" :class="{ 'border-red-500': !vehicleAddModelValid }" id="modelName" placeholder="sultan2" minlength="1" maxlength="250" v-model="vehicleAddModel" />
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isVehicleAdd = false">
                    {{ t('global.cancel') }}
                </button>

                <button type="button" class="px-5 py-2 rounded bg-green-100 hover:bg-green-200 dark:bg-green-600 dark:hover:bg-green-400" @click="addVehicle" v-if="vehicleAddModelValid">
                    {{ t('players.characters.vehicle.add') }}
                </button>
            </template>
        </modal>

        <!-- Edit Licenses -->
        <modal :show.sync="isLicenseEdit">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.characters.license.add') }}
                </h1>
            </template>

            <template #default>
                <MultiSelector :items="licenses" locale="players.characters.license" v-model="licenseForm.licenses" />
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isLicenseEdit = false">
                    {{ t('global.cancel') }}
                </button>

                <button type="button" class="px-5 py-2 rounded bg-green-100 hover:bg-green-200 dark:bg-green-600 dark:hover:bg-green-400" @click="updateLicenses" v-if="licensesChanged">
                    {{ t('players.characters.license.add') }}
                </button>
            </template>
        </modal>

        <!-- View Outfits -->
        <modal :show.sync="isViewingOutfits">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('players.characters.outfits') }}
                </h1>
            </template>

            <template #default>
                <div class="flex gap-3 items-center justify-between">
                    <i class="fas fa-arrow-left cursor-pointer text-3xl" @click="previousOutfit()"></i>

                    <div class="h-outfit flex flex-col gap-3">
                        <img :src="viewingOutfit.showcase_url" class="h-full object-contain" v-handle-error />
                        <div class="flex-shrink-0 text-xl font-medium italic text-center">{{ outfitIndex + 1 }} - {{ viewingOutfit.name }}</div>
                    </div>

                    <i class="fas fa-arrow-right cursor-pointer text-3xl" @click="nextOutfit()"></i>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isViewingOutfits = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <!-- Vehicles -->
        <v-section :noFooter="true">
            <template #header>
                <div class="flex justify-between">
                    <h2>
                        {{ character.vehicles.length > 0 ? t('players.vehicles.vehicles', character.vehicles.length) : t('players.vehicles.no_vehicles') }}

                        <sup :title="t('players.vehicles.vehicle_value')" class="font-mono text-xs -top-6">
                            {{ numberFormat(vehicleValue, false, true) }}
                        </sup>
                    </h2>

                    <!-- Add Vehicle -->
                    <button class="block px-5 py-2 font-semibold text-center text-white rounded bg-success dark:bg-dark-success text-base" @click="isVehicleAdd = true" v-if="$page.auth.player.isSuperAdmin">
                        <i class="fas fa-plus"></i>
                        {{ t('players.characters.vehicle.add') }}
                    </button>
                </div>
            </template>

            <template>
                <div class="grid grid-cols-1 xl:grid-cols-2 3xl:grid-cols-3 wide:grid-cols-4 gap-9 max-h-section overflow-y-auto">
                    <card :key="vehicle.id" v-for="vehicle in character.vehicles" class="relative">
                        <template #header>
                            <img :src="vehicle.image" class="w-full h-40 object-contain rounded mb-5" v-if="vehicle.image" v-handle-error />

                            <h3 class="text-lg text-center font-semibold" :class="{ '-mb-3': vehicle.image }">
                                {{ vehicle.display_name ? vehicle.display_name : vehicle.model_name }}
                            </h3>

                            <div class="absolute bottom-1 left-1.5 text-sm font-semibold">#{{ vehicle.id }}</div>
                            <div class="absolute bottom-1 right-1.5 text-sm font-semibold">{{ vehicle.plate }}</div>
                        </template>

                        <template>
                            <p class="italic">
                                <span :class="vehicle.garage_name ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'" v-html="getGarageLabel(vehicle.garage_name)"></span>
                                <span v-if="vehicle.oil !== null" :class="vehicle.oil > 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'" v-html="getOilLabel(vehicle.oil)"></span>
                            </p>
                        </template>

                        <template #footer>
                            <inertia-link class="block px-3 py-2 text-center text-white bg-blue-600 dark:bg-blue-400 rounded" :href="'/inventory/trunk/' + vehicle.id">
                                <i class="fas fa-briefcase mr-1"></i>
                                {{ t('inventories.show_trunk') }}
                            </inertia-link>

                            <div class="flex justify-between gap-2 w-full" v-if="$page.auth.player.isSuperAdmin">
                                <inertia-link class="block w-full px-3 py-2 text-center text-white mt-3 bg-warning dark:bg-dark-warning rounded" @click="startEditVehicle($event, vehicle)" href="#">
                                    <i class="fas fa-wrench mr-1"></i>
                                    {{ t('players.characters.vehicle.confirm') }}
                                </inertia-link>

                                <inertia-link class="block w-full px-3 py-2 text-center text-white mt-3 bg-red-600 dark:bg-red-400 rounded" @click="deleteVehicle($event, vehicle.id)" href="#">
                                    <i class="fas fa-trash-alt mr-1"></i>
                                    {{ t('global.delete') }}
                                </inertia-link>
                            </div>

                            <button class="block px-2 w-ch-button py-1 text-center text-white absolute top-1 left-1 bg-yellow-400 dark:bg-yellow-400 rounded cursor-pointer" :title="t('players.characters.vehicle.reset_last_garage')" v-if="$page.auth.player.isSuperAdmin" @click="resetLastGarage(vehicle.id, false)">
                                <i class="fas fa-parking"></i>
                            </button>
                            <button class="block px-2 w-ch-button py-1 text-center text-white absolute top-1 left-10 bg-red-400 dark:bg-red-400 rounded cursor-pointer" :title="t('players.characters.vehicle.reset_garage_state')" v-if="$page.auth.player.isSuperAdmin" @click="resetLastGarage(vehicle.id, true)">
                                <i class="fas fa-unlink"></i>
                            </button>

                            <button class="block px-2 cursor-default w-ch-button py-1 text-center text-white absolute top-1 right-20 bg-blue-700 dark:bg-blue-800 rounded" :title="t('players.characters.vehicle.pd_emergency')" v-if="vehicle.emergency === 1">
                                <i class="fas fa-car-alt"></i>
                            </button>
                            <button class="block px-2 cursor-default w-ch-button py-1 text-center text-white absolute top-1 right-20 bg-pink-700 dark:bg-pink-800 rounded" :title="t('players.characters.vehicle.ems_emergency')" v-else-if="vehicle.emergency === 2">
                                <i class="fas fa-ambulance"></i>
                            </button>

                            <inertia-link class="block px-2 py-1 text-center text-white absolute top-1 right-10 bg-blue-600 dark:bg-blue-400 rounded" :href="'/inventory/trunk/' + vehicle.id" :title="t('inventories.show_trunk')">
                                <i class="fas fa-car-side"></i>
                            </inertia-link>
                            <inertia-link class="block px-2 py-1 text-center text-white absolute top-1 right-1 bg-blue-600 dark:bg-blue-400 rounded" :href="'/inventory/glovebox-' + vehicle.id" :title="t('inventories.show_glovebox')">
                                <i class="fas fa-car"></i>
                            </inertia-link>
                        </template>
                    </card>
                </div>
                <p class="text-muted dark:text-dark-muted" v-if="character.vehicles.length === 0">
                    {{ t('players.vehicles.none') }}
                </p>
            </template>
        </v-section>

        <!-- Properties -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('players.properties.properties') }}
                </h2>
            </template>

            <template>
                <div class="grid grid-cols-1 xl:grid-cols-2 3xl:grid-cols-3 wide:grid-cols-4 gap-9 max-h-section overflow-y-auto">
                    <card :key="property.property_id" v-for="(property) in character.properties" :no_body="true" class="relative">
                        <template #header>
                            <div class="absolute top-1 right-1 select-all font-bold text-sm">{{ property.property_id }}</div>

                            <h3 class="mb-2">
                                {{ property.property_address }}
                            </h3>
                            <h4 class="text-blue-700 dark:text-blue-300 font-semibold">
                                <span :title="t('players.properties.cost')">{{ numberFormat(property.property_cost, 0, true) }}</span>
                                <span class="text-gray-500">/</span>
                                <span :title="t('players.properties.rent')">{{ numberFormat(property.property_income, 0, true) }}</span>
                            </h4>
                            <h4 class="text-gray-700 dark:text-gray-300 font-normal text-sm italic" :title="t('players.properties.paid_till')">
                                {{ (property.property_last_pay + (7 * 24 * 60 * 60)) * 1000 | formatTime(true) }}
                            </h4>
                        </template>

                        <template #footer>
                            <div class="flex gap-3">
                                <inertia-link class="block px-3 py-2 mt-3 text-center text-white bg-blue-600 dark:bg-blue-400 rounded" :href="'/inventory/resolve/property-' + property.property_id" :class="$page.auth.player.isSeniorStaff ? 'w-1/2' : 'w-full'">
                                    <i class="fas fa-briefcase mr-1"></i>
                                    {{ t('inventories.show_inv') }}
                                </inertia-link>
                                <button class="block w-1/2 px-3 py-2 mt-3 text-center text-white bg-blue-600 dark:bg-blue-400 rounded" @click="showProperty(property.property_id)" v-if="$page.auth.player.isSeniorStaff">
                                    <i class="fas fa-home mr-1"></i>
                                    {{ t('players.properties.show') }}
                                </button>
                            </div>
                        </template>
                    </card>
                </div>
                <p class="text-muted dark:text-dark-muted" v-if="character.properties.length === 0">
                    {{ t('players.properties.none') }}
                </p>
            </template>

            <template v-if="$page.auth.player.isSeniorStaff">
                <h3 class="mb-4 mt-5 pt-5 border-t-2 border-dashed border-gray-500">
                    {{ t('players.properties.properties_shared') }}
                </h3>
                <div class="grid grid-cols-1 xl:grid-cols-2 3xl:grid-cols-3 wide:grid-cols-4 gap-9 max-h-section overflow-y-auto" v-if="character.accessProperties.length > 0">
                    <card :key="property.property_id" v-for="(property) in character.accessProperties" :no_body="true" class="relative">
                        <template #header>
                            <div class="absolute top-1 right-1 select-all font-bold text-sm">{{ property.property_id }}</div>

                            <h3 class="mb-2">
                                {{ property.property_address }}
                            </h3>
                            <h4 class="text-blue-700 dark:text-blue-300 font-semibold">
                                <span>{{ t('players.properties.access_level') }}:</span>
                                {{ property.keys["c_" + character.id] || "N/A" }}
                            </h4>
                        </template>

                        <template #footer>
                            <div class="flex gap-3">
                                <inertia-link class="block px-3 py-2 mt-3 text-center text-white bg-blue-600 dark:bg-blue-400 rounded" :href="'/inventory/resolve/property-' + property.property_id" :class="$page.auth.player.isSeniorStaff ? 'w-1/2' : 'w-full'">
                                    <i class="fas fa-briefcase mr-1"></i>
                                    {{ t('inventories.show_inv') }}
                                </inertia-link>
                                <button class="block w-1/2 px-3 py-2 mt-3 text-center text-white bg-blue-600 dark:bg-blue-400 rounded" @click="showProperty(property.property_id)" v-if="$page.auth.player.isSeniorStaff">
                                    <i class="fas fa-home mr-1"></i>
                                    {{ t('players.properties.show') }}
                                </button>
                            </div>
                        </template>
                    </card>
                </div>
                <p class="text-muted dark:text-dark-muted" v-if="character.accessProperties.length === 0">
                    {{ t('players.properties.none_access') }}
                </p>
            </template>
        </v-section>

        <!-- Motels -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('players.motels.motels') }}
                </h2>
            </template>

            <template>
                <div class="grid grid-cols-1 xl:grid-cols-2 3xl:grid-cols-3 wide:grid-cols-4 gap-9 max-h-section overflow-y-auto">
                    <card :key="motel.id" v-for="(motel) in motels" :no_body="true" class="relative">
                        <template #header>
                            <h3 class="mb-2">
                                {{ motel.motel }} #{{ motel.room_id }}
                            </h3>
                            <h4 class="text-gray-700 dark:text-gray-300 font-normal text-sm italic" :title="t('players.motels.paid_till')">
                                {{ motel.expire | formatTime(true) }}
                            </h4>
                        </template>
                        <template #footer>
                            <inertia-link class="block px-2 py-1 text-center text-white absolute top-1 right-1 bg-blue-600 dark:bg-blue-400 rounded" v-if="motel.motel in motelMap" :href="'/inventory/motel-' + motelMap[motel.motel] + '-' + motel.room_id + ':1'" :title="t('inventories.show_inv')">
                                <i class="fas fa-archive"></i>
                            </inertia-link>
                        </template>
                    </card>
                </div>
                <p class="text-muted dark:text-dark-muted" v-if="motels.length === 0">
                    {{ t('players.motels.none') }}
                </p>
            </template>
        </v-section>

        <!-- Savings Accounts -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('players.savings.accounts') }}
                </h2>
            </template>

            <template>
                <div class="grid grid-cols-1 xl:grid-cols-2 3xl:grid-cols-3 wide:grid-cols-4 gap-9 max-h-section overflow-y-auto">
                    <card :key="account.id" v-for="account in savingsAccounts" :no_body="true" :no_footer="true" class="relative border" :class="account.character_id === character.id ? '!bg-lime-500 !bg-opacity-20 border-lime-300' : '!bg-blue-500 !bg-opacity-20 border-blue-300'">
                        <template #header>
                            <h3>
                                {{ account.name }} #{{ account.id }}
                            </h3>
                            <h4 class="text-green-600 dark:text-green-300 text-lg mb-2 font-semibold" :class="{ '!text-red-600 dark:!text-red-300': account.balance <= 0 }">
                                {{ numberFormat(account.balance, false, true) }}
                            </h4>

                            <div class="absolute top-0.5 left-1 text-lime-600 dark:text-lime-400 text-sm font-semibold" v-if="account.character_id === character.id">
                                <i class="fas fa-wallet"></i>
                                {{ t('players.savings.owner') }}
                            </div>
                            <div class="absolute top-1 left-1.5 text-blue-600 dark:text-blue-400 text-sm font-semibold" v-else>
                                <i class="fas fa-thumbtack"></i>
                                {{ t('players.savings.access') }}
                            </div>

                            <button class="absolute top-1 right-1.5 text-yellow-600 dark:text-yellow-400 text-sm font-semibold" @click="showSavingsAccount(account)" v-if="perm.check(perm.PERM_SAVINGS_LOGS)">
                                <i class="fas fa-clipboard-list"></i>
                                {{ t('players.savings.show') }}
                            </button>
                        </template>
                    </card>
                </div>
                <p class="text-muted dark:text-dark-muted" v-if="savingsAccounts.length === 0">
                    {{ t('players.savings.none') }}
                </p>
            </template>
        </v-section>

        <modal :show="isShowingProperty">
            <template #header>
                <h1 class="dark:text-white" v-if="propertyData">
                    {{ propertyData.address }} #{{ propertyData.id }}
                </h1>
                <h1 class="dark:text-white" v-else>
                    {{ t('players.properties.property') }}
                </h1>
            </template>

            <template #default>
                <div class="flex justify-center p-4" v-if="isLoadingProperty">
                    <i class="fas fa-spinner animate-spin"></i>
                </div>
                <div class="flex justify-center p-4" v-else-if="!propertyData">
                    {{ t('players.properties.failed_load') }}
                </div>
                <table class="whitespace-nowrap w-full" v-else>
                    <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.properties.player') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.properties.character_id') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.properties.name') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.properties.access') }}</th>
                    </tr>

                    <tr class="border-t border-gray-500" v-for="(access, index) in propertyData.access" :key="index">
                        <td class="px-2 py-0.5">
                            <div class="truncate max-w-56">
                                <a :href="'/players/' + access.license_identifier" class="text-blue-800 dark:text-blue-200">
                                    {{ access.player_name }}
                                </a>
                            </div>
                        </td>
                            <td class="px-2 py-0.5">
                                <a :href="'/players/' + access.license_identifier + '/characters/' + access.character_id" class="text-blue-800 dark:text-blue-200">
                                    #{{ access.character_id }}
                                </a>
                            </td>
                        <td class="px-2 py-0.5">
                            {{ access.full_name }}
                        </td>
                        <td class="px-2 py-0.5 italic">
                            <span v-if="propertyData.renter === access.character_id">{{ t('players.properties.owner') }}</span>
                            <span v-else>{{ t('players.properties.level', access.level) }}</span>
                        </td>
                    </tr>
                </table>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isShowingProperty = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <modal :show="isShowingSavings">
            <template #header>
                <h1 class="dark:text-white" v-if="savingsData && savingsData.account">
                    {{ savingsData.account.name }} #{{ savingsData.account.id }}
                </h1>
                <h1 class="dark:text-white" v-else>
                    {{ t('players.savings.account') }}
                </h1>
            </template>

            <template #default>
                <div class="flex justify-center p-4" v-if="isLoadingSavings">
                    <i class="fas fa-spinner animate-spin"></i>
                </div>
                <div class="flex justify-center p-4" v-else-if="!savingsData">
                    {{ t('players.savings.failed_load') }}
                </div>
                <template v-else>
                    <table class="whitespace-nowrap w-full">
                        <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.savings.player') }}</th>
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.savings.character_id') }}</th>
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.savings.name') }}</th>
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.savings.access') }}</th>
                        </tr>

                        <tr class="border-t border-gray-500" v-for="(access, index) in savingsData.access" :key="index">
                            <td class="px-2 py-0.5">
                                <div class="truncate max-w-56">
                                    <a :href="'/players/' + access.license_identifier" class="text-blue-800 dark:text-blue-200">
                                        {{ access.player_name }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-2 py-0.5">
                                <a :href="'/players/' + access.license_identifier + '/characters/' + access.character_id" class="text-blue-800 dark:text-blue-200">
                                    #{{ access.character_id }}
                                </a>
                            </td>
                            <td class="px-2 py-0.5">
                                {{ access.full_name }}
                            </td>
                            <td class="px-2 py-0.5 italic">
                                <span v-if="savingsData.account.character_id === access.character_id">{{ t('players.savings.owner') }}</span>
                                <span v-else>{{ t('players.savings.access') }}</span>
                            </td>
                        </tr>
                    </table>

                    <div class="my-6 border-t-2 border-dashed border-gray-500"></div>

                    <div class="flex justify-center p-4" v-if="savingsData.logs.length === 0">
                        {{ t('players.savings.logs_none') }}
                    </div>
                    <div v-else>
                        <table class="whitespace-nowrap w-full">
                            <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.savings.character') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.savings.action') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.savings.amount') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.savings.timestamp') }}</th>
                            </tr>

                            <tr class="border-t border-gray-500" v-for="(log, index) in savingsData.logs" :key="index">
                                <td class="px-2 py-0.5">
                                    {{ log.name }}
                                    <a :href="'/players/' + log.license + '/characters/' + log.character_id" class="text-blue-800 dark:text-blue-200">#{{ log.character_id }}</a>
                                </td>
                                <td class="px-2 py-0.5">{{ log.action }}</td>
                                <td class="px-2 py-0.5">
                                    <span class="text-red-800 dark:text-red-200" v-if="log.action === 'withdraw'">
                                        -{{ numberFormat(log.amount, 0, true) }}
                                    </span>

                                    <span class="text-green-800 dark:text-green-200" v-else>
                                        +{{ numberFormat(log.amount, 0, true) }}
                                    </span>
                                </td>
                                <td class="px-2 py-0.5">{{ log.timestamp * 1000 | formatTime(true) }}</td>
                            </tr>
                        </table>
                    </div>
                </template>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isShowingSavings = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Card from './../../Components/Card.vue';
import Badge from './../../Components/Badge.vue';
import Modal from "../../Components/Modal.vue";
import MultiSelector from '../../Components/MultiSelector.vue';

import { ModelSelect } from 'vue-search-select';

import models from "../../data/ped_models.json";

let jobsObject = [];

export default {
    layout: Layout,
    components: {
        VSection,
        Card,
        Badge,
        Modal,
        ModelSelect,
        MultiSelector,
    },
    props: {
        player: {
            type: Object,
            required: true,
        },
        character: {
            type: Object,
            required: true,
        },
        vehicles: {
            type: Object | Array,
            required: true,
        },
        horns: {
            type: Object,
            required: true,
        },
        jobs: {
            type: Object | Array,
            required: true,
        },
        motelMap: {
            type: Object,
            required: true,
        },
        motels: {
            type: Array,
            required: true,
        },
        savingsAccounts: {
            type: Array,
            required: true,
        },
        resetCoords: {
            type: Array,
            required: true,
        },
        vehicleValue: {
            type: Number,
            required: true,
        },
    },
    data() {
        jobsObject = [];

        for (const job in this.jobs) {
            if (Object.hasOwn(Object, job)) continue;

            const jobObject = {
                name: job,
                departments: []
            };

            for (const department in this.jobs[job]) {
                if (Object.hasOwn(Object, department)) continue;

                const departmentObject = {
                    name: department,
                    positions: {}
                };

                for (const position in this.jobs[job][department]) {
                    if (Object.hasOwn(Object, position)) continue;

                    departmentObject.positions[position] = this.jobs[job][department][position].salary;
                }

                jobObject.departments.push(departmentObject)
            }

            jobsObject.push(jobObject);
        }

        const jobs = JSON.parse(JSON.stringify(jobsObject.sort((a, b) => {
            return a.name.toLowerCase() < b.name.toLowerCase() ? -1 : 1
        })));

        for (let x = 0; x < jobs.length; x++) {
            const departments = jobs[x].departments.sort((a, b) => {
                return a.name.toLowerCase() < b.name.toLowerCase() ? -1 : 1
            });

            for (let y = 0; y < departments.length; y++) {
                departments[y].positions = Object.keys(departments[y].positions).reverse();
            }

            jobs[x].departments = departments;
        }

        const paychecks = {};
        for (let x = 0; x < jobsObject.length; x++) {
            const j = jobsObject[x];

            paychecks[j.name] = {};

            for (let y = 0; y < j.departments.length; y++) {
                const d = j.departments[y];

                paychecks[j.name][d.name] = d.positions;
            }
        }

        const money = this.getMoneyLocals();

        let jobName = this.character.jobName,
            departmentName = this.character.departmentName,
            positionName = this.character.positionName;

        if (!jobName || jobName === "Unemployed") {
            jobName = false;
            departmentName = false;
            positionName = false;
        }

        return {
            local: {
                cash: money.cash,
                cashTitle: money.cashTitle,
                stocks: money.stocks
            },
            paycheck: 0,
            form: {
                first_name: this.character.firstName,
                last_name: this.character.lastName,
                date_of_birth: this.character.dateOfBirth,
                gender: this.character.gender,
                backstory: this.character.backstory,
                job_name: jobName,
                department_name: departmentName,
                position_name: positionName,
            },
            vehicleAddModel: '',
            location: window.location.href,
            vehicleForm: {
                id: 0,
                owner_cid: 0,
                fuel: 0.0,
                supporter: 0,
                plate: '',
                modifications: {
                    xenon_headlights: false,
                    tire_smoke: '#ffffff',
                    neon_enabled: false,
                    engine: -1,
                    transmission: -1,
                    breaks: -1,
                    neon: '#ffffff',
                    turbo: false,
                    suspension: -1,
                    armor: -1,
                    plate_type: 0,
                    tint: 0,
                    horn: -1,
                },
                repair: false
            },
            licenseForm: {
                licenses: this.character.licenses
            },
            balanceForm: {
                cash: this.character.cash,
                bank: this.character.bank,
                stocks: this.character.stocksBalance
            },
            licenses: this.getAvailableLicenses(),
            isTattooRemoval: false,
            isResetSpawn: false,
            formattedJobs: jobs,
            paychecks: paychecks,
            isVehicleLoading: false,
            isVehicleEdit: false,
            vehicleEditError: null,
            isVehicleAdd: false,
            isLicenseEdit: false,
            isOffline: false,
            isRefreshingEmail: false,

            preloadedOutfits: false,
            isViewingOutfits: false,
            outfitIndex: 0,

            isShowingSavings: false,
            isLoadingSavings: false,
            savingsData: false,

            isShowingProperty: false,
            isLoadingProperty: false,
            propertyData: false
        };
    },
    computed: {
        licensesChanged() {
            const licenses = this.character.licenses.sort();
            const formLicenses = this.licenseForm.licenses.sort();

            return JSON.stringify(licenses) !== JSON.stringify(formLicenses);
        },
        characterEdited() {
            if (this.form.first_name.trim() !== this.character.firstName) return true;
            if (this.form.last_name.trim() !== this.character.lastName) return true;
            if (this.form.date_of_birth !== this.character.dateOfBirth) return true;
            if (Number.parseInt(this.form.gender) !== this.character.gender) return true;
            if (this.form.backstory.trim() !== this.character.backstory) return true;

            return false;
        },
        balanceEdited() {
            if (Number.parseInt(this.balanceForm.cash) !== this.character.cash) return true;
            if (Number.parseInt(this.balanceForm.bank) !== this.character.bank) return true;
            if (Number.parseInt(this.balanceForm.stocks) !== this.character.stocksBalance) return true;

            return false;
        },
        vehicleAddModelValid() {
            return this.vehicles.includes(this.vehicleAddModel);
        },
        viewingOutfit() {
            return this.character.outfits[this.outfitIndex];
        }
    },
    methods: {
        previousOutfit() {
            this.outfitIndex--;

            if (this.outfitIndex < 0) {
                this.outfitIndex += this.character.outfits.length;
            }
        },
        nextOutfit() {
            this.outfitIndex = (this.outfitIndex + 1) % this.character.outfits.length;
        },
        viewOutfits() {
            if (!this.preloadedOutfits) {
                this.preloadedOutfits = true;

                for (const outfit of this.character.outfits) {
                    const { showcase_url } = outfit;

                    if (!showcase_url) {
                        outfit.showcase_url = "";

                        continue;
                    }

                    (new Image()).src = showcase_url;
                }
            }

            this.isViewingOutfits = true;
        },
        pedModel(hash) {
            if (!hash) {
                return 'unknown';
            }

            return models[hash] || hash;
        },
        async showProperty(propertyId) {
            if (this.isShowingProperty) return;

            this.isShowingProperty = true;
            this.isLoadingProperty = true;
            this.propertyData = false;

            try {
                const data = await _get(`/stocks/property/${propertyId}`);

                if (data?.status) {
                    this.propertyData = data.data;
                }
            } catch (e) {
            }

            this.isLoadingProperty = false;
        },
        async showSavingsAccount(account) {
            if (this.isShowingSavings) return;

            this.isShowingSavings = true;
            this.isLoadingSavings = true;
            this.savingsData = false;

            try {
                const data = await _get(`/savings/${account.id}`);

                if (data?.status) {
                    this.savingsData = data.data;
                }
            } catch (e) {
            }

            this.isLoadingSavings = false;
        },
        getResetCoords() {
            return this.resetCoords
                .map(coords => {
                    return {
                        label: this.t(`players.characters.spawn.${coords}`),
                        key: coords
                    }
                })
                .sort((a, b) => a.label.localeCompare(b.label));
        },
        getMoneyLocals() {
            return {
                cash: this.t("players.edit.cash", this.numberFormat(this.character.money, 0, true)),
                cashTitle: this.t(
                    "players.edit.cash_title",
                    this.numberFormat(this.character.cash, 0, true),
                    this.numberFormat(this.character.bank, 0, true)
                ),
                stocks: this.t("players.edit.stocks", this.numberFormat(this.character.stocksBalance, 0, true))
            };
        },
        getGarageLabel(garage) {
            if (!garage) {
                return this.t('players.vehicles.not_parked');
            }

            if (garage === '*') {
                return this.t('players.vehicles.parked_any');
            }

            if (garage === 'Impound') {
                return this.t('players.vehicles.impounded');
            }

            return this.t('players.vehicles.parked', garage);
        },
        getOilLabel(oil) {
            if (oil > 0) {
                return this.t('players.vehicles.oil_change', oil.toFixed(1));
            }

            return this.t('players.vehicles.oil_change_needed');
        },
        getAvailableLicenses() {
            return ["heli", "fw", "cfi", "hw", "hwh", "perf", "passenger", "management", "military", "utility", "commercial", "special", "hunting", "fishing", "weapon", "mining", "boat", "driver", "press"]
                .sort((a, b) => {
                    const aName = this.t(`players.characters.license.${a}`);
                    const bName = this.t(`players.characters.license.${b}`);

                    return aName.localeCompare(bName);
                });
        },
        setPayCheck() {
            if (!this.form.job_name) {
                this.form.job_name = false;
                this.form.department_name = false;
                this.form.position_name = false;

                this.paycheck = 18;

                return;
            }

            for (let x = 0; x < jobsObject.length; x++) {
                const j = jobsObject[x];

                if (j.name === this.form.job_name) {
                    for (let y = 0; y < j.departments.length; y++) {
                        const d = j.departments[y];

                        if (d.name === this.form.department_name && this.form.position_name in d.positions) {
                            this.paycheck = d.positions[this.form.position_name];
                            return;
                        }
                    }
                }
            }

            this.paycheck = 0;
        },
        submit(isJobUpdate) {
            const form = this.form;

            let query = '';

            if (isJobUpdate) {
                form.first_name = this.character.firstName;
                form.last_name = this.character.lastName;
                form.date_of_birth = this.character.dateOfBirth;
                form.gender = this.character.gender;
                form.backstory = this.character.backstory;
                query = '?jobUpdate=yes';
            } else {
                form.job_name = this.character.jobName;
                form.department_name = this.character.departmentName;
                form.position_name = this.character.positionName;
            }

            this.$inertia.put(`/players/${this.player.licenseIdentifier}/characters/${this.character.id}${query}`, form, { preserveScroll: true })
        },
        async deleteVehicle(e, vehicleId) {
            e.preventDefault();

            if (!confirm(this.t('players.vehicles.delete_vehicle'))) {
                return;
            }

            // Send request.
            await this.$inertia.post(`/vehicles/delete/${vehicleId}`, {}, { preserveScroll: true });
        },
        async resetLastGarage(vehicleId, fullReset) {
            if (!confirm(this.t(`players.characters.vehicle.${fullReset ? 'full_reset_confirm' : 'reset_last_garage_confirm'}`))) {
                return;
            }

            // Send request.
            await this.$inertia.delete(`/vehicles/garage/${vehicleId}/${fullReset ? 'true' : 'false'}`, {}, { preserveScroll: true });
        },
        sortJobs(array, type) {
            switch (type) {
                case 'job':
                case 'department':
                    array.sort((a, b) => {
                        return a.name.toLowerCase() < b.name.toLowerCase() ? -1 : 1
                    });
                    return array;
                case 'position':
                    array.sort((a, b) => {
                        return a.toLowerCase() < b.toLowerCase() ? -1 : 1
                    });
                    return array;
            }

            return [];
        },
        updateJob() {
            this.submit(true);
        },
        startEditVehicle(e, vehicle) {
            e.preventDefault();

            this.vehicleForm = vehicle;
            this.vehicleForm.repair = false;
            this.isVehicleEdit = true;
        },
        async removeTattoos() {
            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/characters/${this.character.id}/remove_tattoos`, {
                zone: $('#zone').val(),
            }, { preserveScroll: true });

            // Reset.
            this.isTattooRemoval = false;
        },
        async resetSpawn() {
            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/characters/${this.character.id}/reset_spawn`, {
                spawn: $('#spawn').val(),
            }, { preserveScroll: true });

            // Reset.
            this.isResetSpawn = false;
        },
        async editVehicle() {
            if (this.isVehicleLoading) {
                return;
            }

            this.isVehicleLoading = true;
            this.vehicleForm.plate = this.vehicleForm.plate.toUpperCase().trim();

            try {
                const response = await _post(`/vehicles/edit/${this.vehicleForm.id}`, this.vehicleForm);

                if (response.status) {
                    $('.overflow-y-auto').scrollTop(0);
                    window.location.reload();
                } else {
                    this.vehicleEditError = response.message;

                    this.isVehicleLoading = false;
                    return;
                }
            } catch (e) {
            }

            this.isVehicleLoading = false;
            this.isVehicleEdit = false;
        },
        async addVehicle() {
            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/characters/${this.character.id}/add_vehicle`, {
                model: this.vehicleAddModel
            }, { preserveScroll: true });

            // Reset.
            this.vehicleAddModel = '';
            this.isVehicleAdd = false;
        },
        async updateLicenses() {
            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/characters/${this.character.id}/update_licenses`, {
                licenses: this.licenseForm.licenses
            }, { preserveScroll: true });

            // Reset.
            this.isLicenseEdit = false;

            this.licenses = this.getAvailableLicenses();
        },
        async refreshEmail() {
            if (this.isRefreshingEmail) {
                return;
            }

            this.isRefreshingEmail = true;

            // Send request.
            await this.$inertia.post(`/players/${this.player.licenseIdentifier}/characters/${this.character.id}/refresh_email`, {}, {
                preserveScroll: true
            });

            this.isRefreshingEmail = false;
        },
        async editBalance() {
            // Send request.
            await this.$inertia.put(`/players/${this.player.licenseIdentifier}/characters/${this.character.id}/edit_balance`, this.balanceForm, { preserveScroll: true });

            const money = this.getMoneyLocals();

            this.local.cash = money.cash;
            this.local.cashTitle = money.cashTitle;
            this.local.stocks = money.stocks;

            this.balanceForm.cash = this.character.cash;
            this.balanceForm.bank = this.character.bank;
            this.balanceForm.stocks = this.character.stocksBalance;
        },
        async loadOfflineStatus() {
            const status = (await this.requestData(`/online/${this.player.licenseIdentifier}`)) || {};

            this.isOffline = !status[this.player.licenseIdentifier];
        }
    },
    mounted() {
        this.setPayCheck();

        this.loadOfflineStatus();
    }
}
</script>
