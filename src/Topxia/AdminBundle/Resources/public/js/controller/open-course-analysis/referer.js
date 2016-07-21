define(function(require, exports, module) {
	require('echarts-debug');
	var EchartsConfig = require('./pieChartsConfig');
	exports.run = function() {
		var myChart = echarts.init(document.getElementById('echats-pie'));
		var config = new EchartsConfig();
		myChart.setOption(config.option());
		var triggerAction = function(action, selected) {
			legend = [];

			for (name in selected) {
				if (selected.hasOwnProperty(name)) {
					legend.push({
						name: name
					});
				}
			}

			myChart.dispatchAction({
				type: action,
				batch: legend
			});
		};


		myChart.on('legendselectchanged', function(obj) {
			triggerAction('legendSelect', obj.selected);
		});
	}
});