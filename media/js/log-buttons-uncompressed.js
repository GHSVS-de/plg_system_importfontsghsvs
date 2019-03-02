plg_system_importfontsghsvs = window.plg_system_importfontsghsvs || {};

(function (document, plg_system_importfontsghsvs)
{
  'use strict';

  var logButtonsEvents = function logButtonsEvents(callback)
	{
	  var logButtonsContainer = document.getElementById("logButtonsContainer");
    var logButtonsOutput    = logButtonsContainer.querySelector(".ajaxOutput");

    logButtonsContainer.addEventListener("click", function (event)
		{
      if (event.target.classList.contains("deleteFile"))
			{
        event.preventDefault();
				logButtonsOutput.innerHTML = "";
        callback({
          plugin: "PlgSystemImportFontsGhsvsDeleteLogFile",
					ajaxOutput: logButtonsOutput
        });
      }
			else if (event.target.classList.contains('showFilePath'))
			{
        event.preventDefault();
				logButtonsOutput.innerHTML = '';
        callback({
          plugin: 'PlgSystemImportFontsGhsvsShowLogFilePath',
					ajaxOutput: logButtonsOutput
        });
      }
			else if (event.target.classList.contains('showFile'))
			{
        event.preventDefault();
				logButtonsOutput.innerHTML = '';
        callback({
          plugin: 'PlgSystemImportFontsGhsvsShowLogFile',
					ajaxOutput: logButtonsOutput
        });
      }
    });
  };
	logButtonsEvents(plg_system_importfontsghsvs.getJson);
})(document, plg_system_importfontsghsvs);
