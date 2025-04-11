class Negotiator {
	constructor() {
		this.id = Math.floor(Math.random() * 1000 * 1000);

		this.channel = null;
		this.timeout = false;
	}

	open(name, callback) {
		if (this.channel) return;

		this.master = false;
		this.tabs = [this.id];

		this.channel = new BroadcastChannel(name);

		this.channel.postMessage({
			type: "hello",
			tab: this.id,
		});

		this.timeout = setTimeout(() => {
			if (this.master) return;

			this.master = this.id;

			callback(true);
		}, 200);

		this.channel.addEventListener("message", event => {
			const type = event.data.type,
				tab = event.data.tab;

			if (tab === this.id) return;

			clearTimeout(this.timeout);

			if (type === "hello" || type === "hi") {
				if (!this.tabs.includes(tab)) {
					this.tabs.push(tab);
				}

				if (type === "hello") {
					this.channel.postMessage({
						type: "hi",
						tab: this.id,
					});
				}
			} else if (type === "goodbye") {
				this.tabs = this.tabs.filter(tb => tb !== tab);
			}

			this.master = Math.max(...tabs);

			callback(this.master === this.id);
		});
	}

	close() {
		if (!this.channel) return;

		clearTimeout(this.timeout);

		this.channel.postMessage({
			type: "goodbye",
			tab: this.id,
		});

		this.channel.close();

		this.channel = null;
	}
}

const MasterTab = {
	async install(Vue, options) {
		const negotiators = {};

		Vue.prototype.openMasterTab = (name, callback) => {
			if (negotiators[name]) {
				negotiators[name].close();
			}

			const negotiator = new Negotiator();
			negotiator.open(name, callback);

			negotiators[name] = negotiator;
		};

		Vue.prototype.closeMasterTab = () => {
			const negotiator = negotiators[name];

			if (!negotiator) {
				return;
			}

			negotiator.close();
		};

		window.addEventListener("beforeunload", () => {
			for (const negotiator of Object.values(negotiators)) {
				negotiator.close();
			}
		});
	},
};

export default MasterTab;
