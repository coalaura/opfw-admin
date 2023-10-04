import Player from './Player';
import Notifier from './Notifier';
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
        this.players = {};

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

        for (let x = 0; x < rawData.length; x++) {
            rawData[x] = Player.fixData(rawData[x]);

            this.updatePlayer(rawData[x], selectedInstance);
        }

        this.instances = Object.entries(this.instances).map(entry => {
            return {
                id: parseInt(entry[0]),
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

    updatePlayer(rawPlayer, selectedInstance) {
        const id = Player.getPlayerID(rawPlayer),
            flags = Player.getPlayerFlags(rawPlayer);

        if (flags.fakeDisconnected) {
            return;
        }

        if (rawPlayer.character) {
            this.stats.loaded++;
        } else {
            this.unloadedPlayers.push(rawPlayer);

            this.stats.unloaded++;
        }

        this.stats.total++;

        const characterFlags = Character.getCharacterFlags(rawPlayer.character);
        if (!characterFlags.spawned) {
            return;
        }

        const instance = parseInt(rawPlayer.instance);
        if (rawPlayer.character) {
            this.instances[instance] = (this.instances[instance] || 0) + 1;
        }

        if (instance !== selectedInstance) {
            return;
        }

        if (id in this.players) {
            this.players[id].update(rawPlayer, this.staffMembers);
        } else {
            this.players[id] = new Player(rawPlayer, this.staffMembers);
        }

        const player = this.players[id];

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

    isActive(id) {
        return !!this.get(id);
    }

    get(id) {
        return id in this.players ? this.players[id] : null;
    }

    remove(id) {
        delete this.players[id];
    }

    eachPlayer(callback) {
        for (const id in this.players) {
            if (!this.players.hasOwnProperty(id)) continue;

            callback(id, this.players[id]);
        }
    }

    getPlayerListInfo(player) {
        return {
            is_staff: player.player.isStaff,
            name: player.character ? player.character.name : 'N/A',
            playerName: player.player.name,
            license: player.player.license,
            invisible: player.invisible.time,
            cid: player.character ? player.character.id : 0,
            source: player.player.source,
            onDuty: player.onDuty
        };
    }
}

export default PlayerContainer;
