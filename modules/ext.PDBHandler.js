/**
 * ext.PDBHandler module
 */
'use strict';
(function ($, window, document) {

function hasWebGLEnabled() {
	var canvas = document.createElement('canvas'),
		contextNames = ['webgl','experimental-webgl','moz-webgl','webkit-3d'],
		i, gl;
	try {
		for (i = 0; i < contextNames.length; i++) {
			gl = canvas.getContext(contextNames[i]);
			if (gl)
				return true;
		}
	} catch(e) {
		return false;
	}
	return false;
}

if (window.pdbHandlerParams !== undefined && hasWebGLEnabled()) {
	$.each(window.pdbHandlerParams, function(glmolId, pdbUrl) {
		$.ajax({
			url: pdbUrl,
			success: function(pdbData) {
				var area = $('#' + glmolId),
					width = area.width(),
					height = area.height();
				if (height < width) {
					// grow drawing area
					area.height(width);
					if (area.closest('li.gallerybox').length) {
						// adjust gallery box height
						var mh = parseInt(area.parent('div').css('margin-top'));
						area.parent('div').css('margin', mh - (width - height) / 2.0 + 'px auto');
					}
				}
				area.empty();
				var model = new GLmol(glmolId, true);
				model.defineRepresentation = function () {
					var all = this.getAllAtoms();
					this.colorChainbow(all);
					this.drawCartoon(this.modelGroup, all);
					this.drawCartoonNucleicAcid(this.modelGroup, all);
					this.drawNucleicAcidStick(this.modelGroup, all);
					this.camera = this.perspectiveCamera;
				};
				model.loadMoleculeStr(false, pdbData);
			}
		});
	});
}

}(jQuery, window, document));
