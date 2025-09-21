import Player from './Player.js';
import Notifier from './Notifier.js';
import { Character } from './Objects.js';

class PlayerContainer {
    constructor(staffMembers) {
        this.staffMembers = staffMembers;
        this.players = {};
        this.vehicles = {};

        this.invisible = [];
        this.on_duty = {
            pd: [],
            ems: []
        };
        this.staff = [];
        this.resetStats();

        this.unloadedPlayers = [];

        this.mainInstance = 1;

        this.instances = [];
        this.instanceNames = {};

        this.notifier = new Notifier();
    }

    resetStats() {
        this.stats = {
            police: 0,
            ems: 0,
            staff: 0,
            loaded: 0,
            unloaded: 0,
            total: 0
        };
    }

    updatePlayers(rawData, vue, selectedInstance, mainInstance) {
        this.resetStats();

        this.vehicles = {};

        this.unloadedPlayers = [];

        this.invisible = [];
        this.on_duty = {
            pd: [],
            ems: []
        };
        this.staff = [];

        this.instances = {
            [mainInstance]: 0
        };

        for (const source in rawData) {
            rawData[source] = Player.fixData(rawData[source]);

            this.updatePlayer(source, rawData[source], selectedInstance);
        }

        for (const source in this.players) {
            if (!rawData[source]) {
                this.remove(source);
            }
        }

        this.instances = Object.entries(this.instances).map(entry => {
            const id = Number.parseInt(entry[0]);

            return {
                id: id,
                name: this.instanceNames[id] || `#${id}`,
                count: entry[1]
            };
        }).sort((a, b) => a.id > b ? 1 : (a.id < b.id ? -1 : 0));

        this.invisible.sort((b, a) => (a.invisible > b.invisible) ? 1 : ((b.invisible > a.invisible) ? -1 : 0));

        this.on_duty.pd.sort((a, b) => (a.source > b.source) ? 1 : ((b.source > a.source) ? -1 : 0));
        this.on_duty.ems.sort((a, b) => (a.source > b.source) ? 1 : ((b.source > a.source) ? -1 : 0));

        this.staff.sort((a, b) => (a.source > b.source) ? 1 : ((b.source > a.source) ? -1 : 0));

        this.notifier.checkPlayers(this, vue);

        if (mainInstance) {
            this.mainInstance = mainInstance;
        } else {
            const possibleInstances = this.instances.filter(instance => instance.count > 1);

            this.mainInstance = possibleInstances.length > 0 ? possibleInstances[0].id : 1;
        }

        this.unloadedPlayers.sort((a, b) => {
            return a.source - b.source;
        });
    }

    updatePlayer(source, rawPlayer, selectedInstance) {
        const flags = Player.getPlayerFlags(rawPlayer);

        if (flags.fakeDisconnected) {
            return;
        }

        if (rawPlayer.character) {
            this.stats.loaded++;
        } else {
            this.unloadedPlayers.push({
                ...rawPlayer,
                isStaff: this.staffMembers.includes(rawPlayer.license)
            });

            this.stats.unloaded++;
        }

        this.stats.total++;

        const characterFlags = Character.getCharacterFlags(rawPlayer.character);
        if (!characterFlags.spawned) {
            return;
        }

        const instance = Number.parseInt(rawPlayer.instance);

        if (rawPlayer.character) {
            this.instances[instance] = (this.instances[instance] || 0) + 1;
            this.instanceNames[instance] = `${rawPlayer.instanceName} #${instance}`;
        }

        if (instance !== selectedInstance) {
            return;
        }

        if (source in this.players) {
            this.players[source].update(rawPlayer, this.staffMembers);
        } else {
            this.players[source] = new Player(rawPlayer, this.staffMembers);
        }

        const player = this.players[source];

        const vehicle = player.getVehicleID();

        if (vehicle) {
            if (!(vehicle in this.vehicles)) {
                this.vehicles[vehicle] = {
                    driver: null,
                    passengers: []
                };
            }

            const info = {
                license: player.player.license,
                name: player.character.name
            };

            if (player.character.isDriving) {
                this.vehicles[vehicle].driver = info;
            } else {
                this.vehicles[vehicle].passengers.push(info);
            }
        }

        if (player.character) {
            if (player.invisible.value) {
                this.invisible.push(this.getPlayerListInfo(player));
            }
        }

        if (player.player.isStaff) {
            this.stats.staff++;

            this.staff.push(this.getPlayerListInfo(player));
        }

        if (player.onDuty === 'police') {
            this.stats.police++;

            this.on_duty.pd.push(this.getPlayerListInfo(player));
        } else if (player.onDuty === 'medical') {
            this.stats.ems++;

            this.on_duty.ems.push(this.getPlayerListInfo(player));
        }
    }

    isActive(source) {
        return !!this.get(source);
    }

    shouldDrawPlayerMarker(source, instance) {
        const player = this.get(source);

        if (!player || !player.character) return false;

        if (player.instance !== Number.parseInt(instance)) return false;

        return true;
    }

    get(source) {
        return source in this.players ? this.players[source] : null;
    }

    remove(source) {
        delete this.players[source];
    }

    eachPlayer(callback) {
        for (const source in this.players) {
            if (!Object.hasOwn(this.players, source)) continue;

            callback(source, this.players[source]);
        }
    }

    getPlayerListInfo(player) {
        return {
            is_staff: player.player.isStaff,
            name: player.character ? player.character.name : 'N/A',
            playerName: player.player.name,
            license: player.player.license,
            invisible: player.invisible.time,
            csource: player.character ? player.character.source : 0,
            source: player.player.source,
            onDuty: player.onDuty
        };
    }
}

export default PlayerContainer;
