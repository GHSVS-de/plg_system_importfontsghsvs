plg_system_importfontsghsvs = window.plg_system_importfontsghsvs || {};

(function (document, plg_system_importfontsghsvs)
{
  'use strict';

  var renewalButtonsEvents = function renewalButtonsEvents(callback)
	{
	  var renewalButtonsContainer = document.getElementById('renewalButtonsContainer');
    var renewalButtonsOutput = renewalButtonsContainer.querySelector('.ajaxOutput');

    renewalButtonsContainer.addEventListener('click', function (event)
		{
      if (event.target.classList.contains('deleteFile'))
			{
        event.preventDefault();
				renewalButtonsOutput.innerHTML = '';
        callback({
          plugin: 'PlgSystemImportFontsGhsvsDeleteRenewalFile',
					ajaxOutput: renewalButtonsOutput
        });
      }
      else if (event.target.classList.contains('folderSize'))
			{
        event.preventDefault();
				renewalButtonsOutput.innerHTML = '';
        callback({
          plugin: 'PlgSystemImportFontsGhsvsFolderSize',
					ajaxOutput: renewalButtonsOutput
        });
      }
    });
  };
	renewalButtonsEvents(plg_system_importfontsghsvs.getJson);
})(document, plg_system_importfontsghsvs);
