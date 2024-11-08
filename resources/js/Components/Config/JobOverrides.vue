<template>
    <div class="relative">
        <div class="absolute top-0 right-0 left-0 bottom-0 backdrop-blur-md flex justify-center items-center" v-if="isLoading">
            <i class="fas fa-spinner animate-spin text-xl"></i>
        </div>

        <div class="flex gap-3 mb-3">
            <button class="font-semibold cursor-pointer text-sm" @click="addBlankOverride()">
                <i class="fas fa-plus mr-1"></i>
                {{ t('tools.config.add_override') }}
            </button>
            <button class="font-semibold cursor-pointer text-sm ml-2" @click="isImportingJob = true">
                <i class="fas fa-address-card mr-1"></i>
                {{ t('tools.config.import_jobs') }}
            </button>
            <button class="font-semibold cursor-pointer text-sm ml-2" @click="isReadingConfig = true">
                <i class="fab fa-readme mr-1"></i>
                {{ t('tools.config.read_config') }}
            </button>
            <button class="font-semibold cursor-pointer text-sm ml-2" @click="exportConfig()">
                <i class="fas fa-cloud-download-alt mr-1"></i>
                {{ t('tools.config.export_config') }}
            </button>
        </div>

        <div class="flex flex-col gap-3">
            <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm" v-for="(override, index) in overrides" :key="index">
                <tr class="border-b-2 border-gray-500 text-left">
                    <th class="px-2 py-1">
                        <button class="font-semibold cursor-pointer text-sm" @click="overrides.splice(index, 1)" :title="t('tools.config.remove_override')">
                            <i class="fas fa-minus"></i>
                        </button>
                    </th>
                    <th class="px-2 py-1">{{ t('tools.config.job_name') }}</th>
                    <th class="px-2 py-1">{{ t('tools.config.department_name') }}</th>
                    <th class="px-2 py-1">
                        <button class="font-semibold cursor-pointer text-sm mr-1" @click="addPosition(override)">
                            <i class="fas fa-plus"></i>
                        </button>

                        {{ t('tools.config.positions') }}
                    </th>
                </tr>

                <tr class="border-t border-gray-500">
                    <td class="px-2 py-1" colspan="2">
                        <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': override.jobName }" v-model="override.jobName" placeholder="Law Enforcement" />
                    </td>

                    <td class="px-2 py-1">
                        <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': override.departmentName }" v-model="override.departmentName" placeholder="SASP" />
                    </td>

                    <td class="px-2 py-1">
                        <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm border-collapse">
                            <tr v-for="(position, indexP) in override.positions" :key="indexP" class="border border-gray-500" :title="position.exists ? t('tools.config.position_exists') : ''">
                                <td class="px-2 py-1">
                                    <button class="font-semibold cursor-pointer text-sm" @click="override.positions.splice(indexP, 1)" :title="t('tools.config.remove_override')">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </td>
                                <td class="px-2 py-1">
                                    <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="position.exists ? '!border-teal-600 !dark:border-teal-400' : (position.name ? '!border-lime-600 !dark:border-lime-400' : '')" v-model="position.name" placeholder="Corporal" @input="updatePosition(override, position)" />
                                </td>
                                <td class="px-2 py-1">
                                    <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="position.exists ? '!border-teal-600 !dark:border-teal-400' : (position.salary ? '!border-lime-600 !dark:border-lime-400' : '')" v-model="position.salary" type="number" placeholder="12" @input="updatePosition(override, position)" @change="sortPositions(index)" />
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
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
                <label class="block mb-1 font-semibold" for="cluster">{{ t('tools.config.read_from_cluster') }}</label>
                <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="cluster" id="cluster" type="number" min="1" max="100" placeholder="3" />

                <label class="block mb-1 font-semibold mt-5 pt-5 border-t-2 border-dashed border-gray-500" for="cluster">{{ t('tools.config.or_read_text') }}</label>
                <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="reading" id="reading" placeholder="Law Enforcement:SASP:Cadet=70;Probationary Officer=80;Officer=90..." />
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500" @click="isReadingConfig = false">
                    {{ t('global.close') }}
                </button>
                <button type="button" class="px-5 py-2 rounded bg-lime-200 hover:bg-lime-300 dark:bg-lime-600 dark:hover:bg-lime-500" @click="readConfig()" v-if="reading || cluster">
                    {{ t('tools.config.import') }}
                </button>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from '../Modal';

export default {
    name: 'JobOverrides',
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
            isLoading: false,

            isImportingJob: false,
            isReadingConfig: false,

            importing: {
                job: "",
                department: "",
                position: ""
            },

            cluster: "",
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
        doesJobExist(override, position) {
            const job = this.jobs[override.jobName],
                department = job ? job[override.departmentName] : null,
                positionData = department ? department[position.name] : null;

            if (!positionData) {
                return false;
            }

            return positionData.salary === parseInt(position.salary);
        },
        updatePosition(override, position) {
            position.exists = this.doesJobExist(override, position);
        },
        addBlankOverride() {
            this.overrides.push({
                jobName: "",
                departmentName: "",
                positions: [
                    {
                        name: "",
                        salary: 1,
                        exists: false
                    }
                ]
            });
        },
        addPosition(override) {
            override.positions.push({
                name: "",
                salary: 1,
                exists: false
            });
        },
        sortPositions(index) {
            const override = this.overrides[index];

            override.positions.sort((a, b) => b.salary - a.salary);

            for (const position of override.positions) {
                this.updatePosition(override, position);
            }
        },
        importPosition(jobName, departmentName, positionName, salary) {
            const position = {
                name: positionName,
                salary: salary,
                exists: false
            };

            const index = this.overrides.findIndex((override) => override.jobName === jobName && override.departmentName === departmentName);

            if (index === -1) {
                const newIndex = this.overrides.push({
                    jobName: jobName,
                    departmentName: departmentName,
                    positions: [position]
                });

                this.sortPositions(newIndex - 1);
            } else {
                this.overrides[index].positions.push({
                    name: positionName,
                    salary
                });

                this.sortPositions(index);
            }
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
        },
        async readConfig() {
            if (this.isLoading) return;

            this.isReadingConfig = false;

            const cluster = parseInt(this.cluster);

            let reading = this.reading.trim();

            this.cluster = "";
            this.reading = "";

            if (cluster) {
                this.isLoading = true;

                try {
                    const response = await axios.get(`/api/config/${cluster}/job_overrides`),
                        data = response.data;

                    if (!data || !data.status) {
                        throw new Error("Config not found");
                    }

                    reading = data.data;
                } catch (e) {
                    console.error(e);
                }

                this.isLoading = false;
            }

            if (reading.startsWith("job_overrides")) {
                reading = reading.replace(/job_overrides ?= ?/, "").trim().substring(1);
                reading = reading.substring(0, reading.length - 1);
            }

            const overrides = reading.trim().split(",");

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
        },
        exportConfig() {
            let entries = []

            for (const override of this.overrides) {
                const { jobName, departmentName, positions } = override;

                if (!jobName || !departmentName || !positions.length) {
                    continue;
                }

                const positionStr = positions.map(position => `${position.name}=${position.salary}`).join(";");

                entries.push(`${jobName}:${departmentName}:${positionStr}`);
            }

            const config = entries.join(",");

            this.copyToClipboard(`job_overrides = "${config}"`);
        }
    }
}
</script>
