Joomla = window.Joomla || {};
plg_system_importfontsghsvs = window.plg_system_importfontsghsvs || {};

(function (Joomla, plg_system_importfontsghsvs)
{
  'use strict';

  plg_system_importfontsghsvs.getJson = function getJson()
	{
    var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        _ref$plugin = _ref.plugin;
		var plugin = _ref$plugin === void 0 ? '' : _ref$plugin;

		if (plugin)
		{
    	var url = "index.php?option=com_ajax&group=system&plugin=".concat(plugin, "&format=raw");
			var ajaxOutput = _ref.ajaxOutput;
			var Strings = Joomla.getOptions("plg_system_importfontsghsvs");

			ajaxOutput.innerHTML = "<pre class=pre4logOutput>" + Strings.bePatient + "";

    	Joomla.request({
				url: url,
      	headers: {
        	'Content-Type': 'application/json'
      	},
      	onSuccess: function onSuccess(response) {
        	try {
          	var json = JSON.parse(response);
          	if (json && json.html)
						{
            	ajaxOutput.innerHTML = "<pre class=pre4logOutput>" + json.html + "</pre>";
          	}
        	} catch (e) {
						ajaxOutput.innerHTML = "<pre class=pre4logOutput>" + Strings.ajaxError
						+ "<br>" + e
						+ "<br>Response:<br>" + htmlEntities(response)
						+ "</pre>";
          	throw new Error(e);
        	}
      	},
      	onError: function onError(xhr) {
        	Joomla.renderMessages({
          	error: [xhr.response]
        	});
      	}
    	});
		};
  };
	function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
})(Joomla, plg_system_importfontsghsvs);
