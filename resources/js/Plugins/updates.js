const Updates = {
	async install(Vue, options) {
		const subscribers = {};

		let socket;

		function createSocket(instance) {
			if (socket || !Object.keys(subscribers).length) return;

			socket = instance.createSocket("updates", {
				onData: data => {
					for (const name in subscribers) {
						subscribers[name](data);
					}
				},
				onDisconnect: () => {
					socket = false;

					setTimeout(() => {
						createSocket(instance);
					}, 2000);
				},
			});
		}

		function closeSocket() {
			if (!socket || Object.keys(subscribers).length) return;

			socket.close();

			socket = false;
		}

		Vue.prototype.subscribeMisc = function (name, cb) {
			subscribers[name] = cb;

			createSocket(this);
		};

		Vue.prototype.unsubscribeMisc = name => {
			delete subscribers[name];

			closeSocket();
		};
	},
};

export default Updates;
