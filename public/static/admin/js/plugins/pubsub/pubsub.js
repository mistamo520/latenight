function pubsub() {
	this.handlers = {};
}
pubsub.prototype = {
	// 订阅事件
	on : function(eventType, handler) {
		var self = this;
		if (!(eventType in self.handlers)) {
			self.handlers[eventType] = [];
		}
		self.handlers[eventType].push(handler);
		return this;
	},
	// 触发事件(发布事件)
	trigger : function(eventType) {
		var self = this;
		var handlerArgs = Array.prototype.slice.call(arguments, 1);
		for (var i = 0; i < self.handlers[eventType].length; i++) {
			self.handlers[eventType][i].apply(self, handlerArgs);
		}
		return self;
	}
};