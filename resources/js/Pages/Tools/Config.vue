<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('tools.config.title') }}
            </h1>
            <p>
                {{ t('tools.config.description') }}
            </p>
        </portal>

        <div class="rounded-lg shadow bg-secondary dark:bg-dark-secondary max-w-6xl px-8 py-6 mt-14">
            <div class="flex items-center gap-3 mb-4 pb-4 border-b-2 border-dashed border-gray-400">
                <label for="type" class="font-semibold">{{ t('tools.config.type') }}:</label>
                <select class="block w-full px-3 py-1 bg-gray-200 border rounded dark:bg-gray-600" id="type" v-model="type">
                    <option value="job_override">
                        {{ t('tools.config.job_override') }}
                    </option>
                </select>
            </div>

            <div v-if="type === 'job_override'">
                <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm">
                    <tr class="border-b-2 border-gray-500 text-left">
                        <th class="px-2 py-1">
                            <button class="font-semibold cursor-pointer text-sm" @click="addOverride()" :title="t('tools.config.add_override')">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="font-semibold cursor-pointer text-sm ml-2" @click="isImportingJob = true" :title="t('tools.config.import_jobs')">
                                <i class="fas fa-address-card"></i>
                            </button>
                            <button class="font-semibold cursor-pointer text-sm ml-2" @click="isReadingConfig = true" :title="t('tools.config.read_config')">
                                <i class="fas fa-file-upload"></i>
                            </button>
                            <button class="font-semibold cursor-pointer text-sm ml-2" @click="exportConfig()" :title="t('tools.config.export_config')">
                                <i class="fas fa-cloud-download-alt"></i>
                            </button>
                        </th>
                        <th class="px-2 py-1">{{ t('tools.config.job_name') }}</th>
                        <th class="px-2 py-1">{{ t('tools.config.department_name') }}</th>
                        <th class="px-2 py-1">{{ t('tools.config.position_name') }}</th>
                        <th class="px-2 py-1">{{ t('tools.config.salary_name') }}</th>
                    </tr>

                    <tr v-for="(override, index) in overrides" :key="index" class="border-t border-gray-500" :class="{ 'opacity-75': override.valid, '!border-t-4 !border-gray-700 !dark:border-gray-300 border-dashed': !isSameAsLast(index, override) }">
                        <td class="px-2 py-1">
                            <button class="font-semibold cursor-pointer text-sm" @click="removeOverride(index)" :title="t('tools.config.remove_override')">
                                <i class="fas fa-minus"></i>
                            </button>
                        </td>
                        <td class="px-2 py-1">
                            <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': override.jobValid, '!border-teal-600 !dark:border-teal-400': override.jobName }" v-model="override.jobName" placeholder="Law Enforcement" @input="overrideUpdated(index)" />
                        </td>
                        <td class="px-2 py-1">
                            <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': override.departmentValid, '!border-teal-600 !dark:border-teal-400': override.departmentName }" v-model="override.departmentName" placeholder="SASP" @input="overrideUpdated(index)" />
                        </td>
                        <td class="px-2 py-1">
                            <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': override.positionValid, '!border-teal-600 !dark:border-teal-400': override.positionName }" v-model="override.positionName" placeholder="Corporal" @input="overrideUpdated(index)" />
                        </td>
                        <td class="px-2 py-1">
                            <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': override.salaryValid, '!border-teal-600 !dark:border-teal-400': override.salary }" v-model="override.salary" placeholder="$12" @input="overrideUpdated(index)" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <modal :show="isImportingJob">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('tools.config.import_jobs') }}
                </h1>
            </template>

            <template #default>
                <div class="grid grid-cols-3 gap-3">
                    <select class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="importing.job" @change="importing.department = ''; importing.position = ''">
                        <option v-for="job in importableJobs" :key="job" :value="job">
                            {{ job }}
                        </option>
                    </select>

                    <select class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="importing.department" v-if="importing.job" @change="importing.position = ''">
                        <option v-for="department in importableDepartments" :key="department" :value="department">
                            {{ department }}
                        </option>
                    </select>

                    <select class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="importing.position" v-if="importing.job && importing.department">
                        <option v-for="position in importablePositions" :key="position" :value="position.name">
                            {{ position.name }} - ${{ position.salary }}
                        </option>
                    </select>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500" @click="isImportingJob = false">
                    {{ t('global.close') }}
                </button>
                <button type="button" class="px-5 py-2 rounded bg-lime-200 hover:bg-lime-300 dark:bg-lime-600 dark:hover:bg-lime-500" @click="importJobs()" v-if="importing.job">
                    {{ t('tools.config.import') }}
                </button>
            </template>
        </modal>

        <modal :show="isReadingConfig">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('tools.config.read_config') }}
                </h1>
            </template>

            <template #default>
                <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="reading" placeholder="Law Enforcement:SASP:Cadet=70;Probationary Officer=80;Officer=90..." />
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500" @click="isReadingConfig = false">
                    {{ t('global.close') }}
                </button>
                <button type="button" class="px-5 py-2 rounded bg-lime-200 hover:bg-lime-300 dark:bg-lime-600 dark:hover:bg-lime-500" @click="readConfig()" v-if="reading">
                    {{ t('tools.config.import') }}
                </button>
            </template>
        </modal>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import Modal from './../../Components/Modal';

export default {
    layout: Layout,
    components: {
        Modal
    },
    props: {
        jobs: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            isImportingJob: false,
            isReadingConfig: false,
            type: "job_override",

            importing: {
                job: "",
                department: "",
                position: ""
            },

            reading: "",

            overrides: []
        };
    },
    computed: {
        importableJobs() {
            return Object.keys(this.jobs).toSorted();
        },
        importableDepartments() {
            const departments = this.jobs[this.importing.job];

            if (!departments) {
                return [];
            }

            return Object.keys(departments).toSorted();
        },
        importablePositions() {
            const departments = this.jobs[this.importing.job],
                positions = departments ? departments[this.importing.department] : null;

            if (!positions) {
                return [];
            }

            return Object.entries(positions).map(([key, value]) => ({
                name: key,
                salary: value.salary
            })).toSorted((a, b) => a.salary - b.salary);
        }
    },
    methods: {
        isSameAsLast(index, override) {
            if (index === 0) {
                return true;
            }

            return override.jobName === this.overrides[index - 1].jobName && override.departmentName === this.overrides[index - 1].departmentName;
        },
        removeOverride(index) {
            this.overrides.splice(index, 1);

            this.sortOverrides();
        },
        overrideUpdated(index) {
            const override = this.overrides[index];

            const job = this.jobs[override.jobName] ?? null,
                department = job?.[override.departmentName] ?? null,
                position = department?.[override.positionName] ?? null;

            if (override.positionName) {
                if (!override.salary) {
                    override.salary = position ? `$${position.salary}` : "$1";
                } else if (!override.salary.startsWith("$")) {
                    override.salary = `$${override.salary}`;
                }

                if (!override.salary.match(/^\$(\d+)?$/)) {
                    override.salary = position ? `$${position.salary}` : "$1";
                }
            } else {
                override.salary = "";
            }

            override.jobValid = !!job;
            override.departmentValid = !!department;
            override.positionValid = !!position;
            override.salaryValid = !!(position && `$${position.salary}` === override.salary);

            override.valid = override.jobValid && override.departmentValid && override.positionValid && override.salaryValid;
        },
        addOverride() {
            this.importPosition("", "", "", "");
        },
        sortOverrides() {
            this.overrides.sort((a, b) => {
                if (a.jobName === b.jobName) {
                    if (a.departmentName === b.departmentName) {
                        const salaryA = parseInt(a.salary.substring(1)) ?? 0,
                            salaryB = parseInt(b.salary.substring(1)) ?? 0;

                        return salaryA - salaryB;
                    }

                    return a.departmentName.localeCompare(b.departmentName);
                }

                return a.jobName.localeCompare(b.jobName);
            });
        },
        importPosition(jobName, departmentName, positionName, salary) {
            const index = this.overrides.push({
                jobName: jobName,
                departmentName: departmentName,
                positionName: positionName,
                salary: `$${salary}`,

                jobValid: false,
                departmentValid: false,
                positionValid: false,
                salaryValid: false,

                valid: false
            });

            this.overrideUpdated(index - 1);
        },
        importPositions(jobName, departmentName, positions) {
            for (const position in positions) {
                const salary = positions[position].salary;

                this.importPosition(jobName, departmentName, position, salary);
            }
        },
        importJobs() {
            this.isImportingJob = false;

            const job = this.importing.job,
                departments = this.jobs[job];

            if (!departments) {
                return;
            }

            if (!this.importing.department) {
                for (const department in departments) {
                    const positions = departments[department];

                    this.importPositions(job, department, positions);
                }
            } else {
                const department = this.importing.department,
                    positions = departments[department];

                if (!positions) {
                    return;
                }

                if (!this.importing.position) {
                    this.importPositions(job, department, positions);
                } else {
                    const position = this.importing.position,
                        salary = positions[position]?.salary;

                    if (!salary) {
                        return;
                    }

                    this.importPosition(job, department, position, salary);
                }
            }

            this.sortOverrides();
        },
        readConfig() {
            this.isReadingConfig = false;

            if (this.reading.startsWith("job_overrides")) {
                this.reading = this.reading.replace(/job_overrides ?= ?/, "").trim().substring(1);
                this.reading = this.reading.substring(0, this.reading.length - 1);
            }

            const overrides = this.reading.trim().split(",");

            if (!overrides.length) {
                return;
            }

            for (const override of overrides) {
                const parts = override.split(":");

                if (parts.length !== 3) {
                    continue;
                }

                const jobName = parts[0],
                    departmentName = parts[1],
                    positions = parts[2].split(";");

                if (!jobName || !departmentName || !positions.length) {
                    continue;
                }

                for (const position of positions) {
                    const [positionName, salary] = position.split("=");

                    if (!positionName || !salary || !salary.match(/^\d+$/)) {
                        continue;
                    }

                    this.importPosition(jobName, departmentName, positionName, salary);
                }
            }

            this.sortOverrides();
        },
        exportConfig() {
            let object = {};

            for (const override of this.overrides) {
                const { jobName, departmentName, positionName, salary } = override;

                if (!jobName || !departmentName || !positionName || !salary) {
                    continue;
                }

                if (!object[jobName]) {
                    object[jobName] = {};
                }

                if (!object[jobName][departmentName]) {
                    object[jobName][departmentName] = {};
                }

                object[jobName][departmentName][positionName] = parseInt(salary.substring(1));
            }

            let entries = []

            for (const job in object) {
                for (const department in object[job]) {
                    const positions = Object.entries(object[job][department]);

                    entries.push(`${job}:${department}:${positions.map(([position, salary]) => `${position}=${salary}`).join(";")}`);
                }
            }

            const config = entries.join(",");

            this.copyToClipboard(`job_overrides = "${config}"`);
        }
    }
}
</script>
