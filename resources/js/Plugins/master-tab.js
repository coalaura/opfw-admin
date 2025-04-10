const MasterTab = {
	async install(Vue, options) {
		const id = Math.floor(Math.random() * 1000 * 1000);

		let tabs = [id],
			master = false,
			channel = false,
			timeout = false;

		function open(name, callback) {
			if (channel) return;

			channel = new BroadcastChannel(name);

			channel.postMessage({
				type: "hello",
				tab: id,
			});

			timeout = setTimeout(() => {
				if (master) return;

				master = id;

				callback(true);
			}, 200);

			channel.addEventListener("message", event => {
				const type = event.data.type,
					tab = event.data.tab;

				if (tab === id) return;

				clearTimeout(timeout);

				if (type === "hello" || type === "hi") {
					if (!tabs.includes(tab)) {
						tabs.push(tab);
					}

					if (type === "hello") {
						channel.postMessage({
							type: "hi",
							tab: id,
						});
					}
				} else if (type === "goodbye") {
					tabs = tabs.filter(tb => tb !== tab);
				}

				master = Math.max(...tabs);

				callback(master === id);
			});
		}

		function close() {
			if (!channel) return;

			clearTimeout(timeout);

			channel.postMessage({
				type: "goodbye",
				tab: id,
			});

			channel.close();

			channel = false;
		}

		Vue.prototype.openMasterTab = (name, callback) => {
			open(name, callback);
		};

		Vue.prototype.closeMasterTab = () => {
			close();
		};

		window.addEventListener("beforeunload", () => {
			close();
		});
	},
}

export default MasterTab;
