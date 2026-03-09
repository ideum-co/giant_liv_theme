/*!
 * REVOLUTION 6.0.0 EDITOR LAYERACTION JS
 * @version: 1.0 (01.07.2019)
 * @author ThemePunch
*/

;RVS.LIB.ACTION_WITH_TRGT = [];
RVS.LIB.ACTIONTYPES = {};

(function() {
	var xmn = ["X","M","N"];


	/*
	INITIALISE THE BASIC LISTENERS, INPUT MANAGEMENTS ETC
	*/
	RVS.F.initLayerActions = function() {
		initActions();
		initLocalInputBoxes();
		initLocalListeners();
	};


	/*
	LAYER ACTIONS
	*/
	RVS.F.openLayerActions = function() {
		if (RVS.selLayers.length>0) {
			RVS.S.actionIdx=undefined;
			jQuery('#no_action_selected').show();
			jQuery('#action_inputs, .la_settings').hide();

			RVS.F.initActionsOfLayers();
			RVS.F.RSDialog.create({modalid:'rbm_layer_action', bgopacity:0.5});


		} else {
			RVS.F.showInfo({content:RVS_LANG.noLayersSelected, type:"warning", showdelay:0, hidedelay:3, hideon:"", event:"" });
		}
	};

	/*
	SELECT LAYER ACTION
	*/
	RVS.F.selectLayerAction = function() {
		jQuery('.actionselected').removeClass("actionselected");
		if (RVS.S.actionIdx===undefined || RVS.S.actionIdx<0 || RVS.L[RVS.selLayers[0]].actions.action.length==0) {
			jQuery('#action_inputs, .la_settings').hide();
			jQuery('#no_action_selected').show();
			return false;
		} else {

			jQuery('#action_inputs').show();
			jQuery('.la_settings, #no_action_selected').hide();

			if (RVS.LIB.ACTIONTYPES[RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action]!==undefined)
				jQuery(RVS.LIB.ACTIONTYPES[RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action].inputs).show();

			if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].layer_target!==undefined)
				RVS.S.actionTrgtLayerId = RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].layer_target || "none";

			if (jQuery.inArray(RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action,["scroll_under","scrollto"])>=0) {
				if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action_easing===undefined) RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action_easing="power1.inOut";
				if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action_speed===undefined) RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action_speed="1000ms";
			}

			if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action==="link") {
				if (RVS.L[RVS.selLayers[0]].type==="column" || RVS.L[RVS.selLayers[0]].type==="row" || RVS.L[RVS.selLayers[0]].type==="group") RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].link_type="jquery";
				else
				if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].link_type===undefined) RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].link_type="a";
			}
			if (jQuery.inArray(RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action,["start_in","start_out","start_frame","next_frame","prev_frame","toggle_layer","toggle_frames"])>=0)
				if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].updateChildren===undefined) RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].updateChildren=true;

			if (RVS.S.actionTrgtLayerId!==undefined) {
				var LREF;
				//FIND THE CORRECT TARGET IN STATIC OR NORMAL LAYERS
				if ((""+RVS.S.actionTrgtLayerId).indexOf("static-")>=0 && RVS.SLIDER.staticSlideId!==undefined && RVS.SLIDER[RVS.SLIDER.staticSlideId]!==undefined && RVS.SLIDER[RVS.SLIDER.staticSlideId].layers!==undefined)
					 LREF = RVS.SLIDER[RVS.SLIDER.staticSlideId].layers[(""+RVS.S.actionTrgtLayerId).replace("static-","")];
				else if (RVS.L[RVS.S.actionTrgtLayerId]!==undefined) {
					LREF = RVS.L[RVS.S.actionTrgtLayerId];
					RVS.H[RVS.S.actionTrgtLayerId].w.addClass("actionselected");
				}

				if (LREF !==undefined) {
					RVS.F.checkToggleLayerType(RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx], LREF,2);
					jQuery('#overtake_frame_1_control')[0].checked = LREF.timeline.frames.frame_1.timeline.actionTriggered;
					jQuery('#overtake_frame_999_control')[0].checked = LREF.timeline.frames.frame_999.timeline.actionTriggered;
					RVS.F.turnOnOffVisUpdate({input:document.getElementById('overtake_frame_1_control')});
					RVS.F.turnOnOffVisUpdate({input:document.getElementById('overtake_frame_999_control')});
					jQuery('#la_triggerMemory').val(LREF.actions.triggerMemory).ddTP('change');
					RVS.F.updatePlayFrameXOnlyOnAction(null,"X");
					RVS.F.updatePlayFrameXOnlyOnAction(null,"N");
					RVS.F.updatePlayFrameXOnlyOnAction(null,"M");
				}
			}

			RVS.F.updateEasyInputs({container:jQuery('#rbm_layer_action'), path:RVS.S.slideId+".layers."+RVS.selLayers[0]+".", trigger:"init"});
			RVS.F.upadteLayerTargetDropDowns({action:RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action, targetid:RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].layer_target});
			RVS.F.updateSlideList({action:RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action, targetid:RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].jump_to_slide});
			RVS.F.updateLinkTypes({action:RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action, linktype:RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].link_type});

			jQuery('#layer_action_fake').html(RVS.LIB.ACTIONTYPES[RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action].name);
		}
	};

	RVS.F.updateReferencigToggleLayerType = function() {
		for (var i in RVS.L) {
			if (RVS.L[i].actions && RVS.L[i].actions.action.length>0)
				for (var j in RVS.L[i].actions.action) {
					if (!RVS.L[i].actions.action.hasOwnProperty(j)) continue;
					var action = RVS.L[i].actions.action[j];
					if (action.action==="toggle_layer")	{
						//FIND THE CORRECT TARGET IN STATIC OR NORMAL LAYERS
						if ((""+action.layer_target).indexOf("static-")>=0 && RVS.SLIDER.staticSlideId!==undefined && RVS.SLIDER[RVS.SLIDER.staticSlideId]!==undefined && RVS.SLIDER[RVS.SLIDER.staticSlideId].layers!==undefined)
							RVS.F.checkToggleLayerType(action, (RVS.SLIDER[RVS.SLIDER.staticSlideId].layers[(""+action.layer_target).replace("static-","")]), 1);
						else
						if (RVS.L[action.layer_target]!==undefined) RVS.F.checkToggleLayerType(action, RVS.L[action.layer_target], 1);
					}
				}

		}
	}

	RVS.F.checkToggleLayerType = function(action, LREF, how) {
		if (LREF!==undefined && action.action==="toggle_layer") {
			//On Select read the current actionTriggered Type. If Exists use it, if not. set it.
			if (LREF.timeline.frames.frame_1.timeline.actionTriggered!==undefined && how==1) {
				action.toggle_layer_type = LREF.timeline.frames.frame_1.timeline.actionTriggered ? "hidden" : "visible";
			} else if (action.toggle_layer_type==="visible")
				LREF.timeline.frames.frame_1.timeline.actionTriggered = false;
			else
				LREF.timeline.frames.frame_1.timeline.actionTriggered = true;
			LREF.timeline.frames.frame_999.timeline.actionTriggered = true;
		}
		return action;
	}



	/*
	CHECK IF A LAYER FRAME IS TRIGGERED BY ANY OTHER LAYER
	*/
	RVS.F.layerFrameTriggered = function(_) {
		var uid = parseInt(_.layerid,0);
		return RVS.L[uid].timeline.frames[_.frame].timeline.actionTriggered;
	};

	function layerFrameTriggeredBy_subrutine_a(isstatic,trgt,uid,DS_RND) {
		return (isstatic && trgt==="static-"+uid) || (isstatic && RVS.SLIDER.slideIDs[DS_RND]===RVS.S.slideId && parseInt(trgt,0) === parseInt(uid,0)) || (!isstatic && parseInt(trgt,0) === parseInt(uid,0));
	}
	function layerFrameTriggeredBy_subrutine_b(a,frame) {

		return ((a.action==="start_in" && (frame==="any" || frame==="frame_1")) ||
				(a.action==="start_out" && (frame==="any" || frame==="frame_999"))  ||
				(a.action==="toggle_layer" && (frame==="any" || frame==="frame_1" || frame==="frame_999")) ||
				(a.action==="toggle_frames" && (frame==="any" || a.gotoframeM===frame || a.gotoframeN===frame)) ||
				(a.action==="start_frame" && (frame==="any" || a.gotoframe===frame)))
	}

	RVS.F.layerFrameTriggeredBy = function(o) {
		var uid = parseInt(o.layerid,0),
			triggeredby = [],
			isstatic = (""+RVS.S.slideId).indexOf("static")>=0,
			DS_RND = o.src==undefined &&  isstatic ? RVS.SLIDER.slideIDs.length-1 : -1,
			DS_DONE = false;

		while (!DS_DONE) {
			var layers = o.src===undefined ? DS_RND===-1 ? RVS.L : RVS.SLIDER[RVS.SLIDER.slideIDs[DS_RND]].layers : o.src;
			for (var i in layers) {
				if ((o.all===undefined && triggeredby.length>0) || !layers.hasOwnProperty(i)) continue;
				if (layers[i].actions !==undefined) {
					//ACTIVE CHECKS
					for (var j in layers[i].actions.action)	{
						if((o.all===undefined && triggeredby.length>0) || !layers[i].actions.action.hasOwnProperty(j) || layers[i].actions.action[j].layer_target===undefined || layers[i].actions.action[j].ajaxSettings!==undefined || layers[i].actions.action[j].fn!==undefined || layers[i].actions.action[j].guid!==undefined || layers[i].actions.action[j].lastModified!==undefined) continue;
						if (layerFrameTriggeredBy_subrutine_a(isstatic,layers[i].actions.action[j].layer_target,uid,DS_RND) && layerFrameTriggeredBy_subrutine_b(layers[i].actions.action[j],o.frame)) triggeredby.push({icon:RVS.F.getLayerIcon(layers[i].linebreak ? 'linebreak' : layers[i].type,layers[i].subtype), action:layers[i].actions.action[j].action,  uid : parseInt(layers[i].uid,0), alias:layers[i].alias, slide:o.src===undefined &&  DS_RND!==-1 ? RVS.SLIDER.slideIDs[DS_RND] : RVS.S.slideId});

					}
					// INACTIVE CHECKS
					if (layers[i].actions.inactive_actions!==undefined)
					for (var j in layers[i].actions.inactive_actions)	{
						if((o.all===undefined && triggeredby.length>0) || !layers[i].actions.inactive_actions.hasOwnProperty(j) || layers[i].actions.inactive_actions[j].layer_target===undefined || layers[i].actions.inactive_actions[j].ajaxSettings!==undefined || layers[i].actions.inactive_actions[j].fn!==undefined || layers[i].actions.inactive_actions[j].guid!==undefined || layers[i].actions.inactive_actions[j].lastModified!==undefined) continue;
						if (layerFrameTriggeredBy_subrutine_a(isstatic,layers[i].actions.inactive_actions[j].layer_target,uid,DS_RND) && layerFrameTriggeredBy_subrutine_b(layers[i].actions.inactive_actions[j],o.frame)) triggeredby.push({icon:RVS.F.getLayerIcon(layers[i].linebreak ? 'linebreak' : layers[i].type,layers[i].subtype), action:layers[i].actions.inactive_actions[j].action,  uid : parseInt(layers[i].uid,0), alias:layers[i].alias, slide:o.src===undefined &&  DS_RND!==-1 ? RVS.SLIDER.slideIDs[DS_RND] : RVS.S.slideId});

					}
				}
			}
			DS_RND--;
			DS_DONE = DS_RND<0;
		}

		if (triggeredby.length===0) triggeredby.push({uid:"", alias:""})

		if (o.all)
			return triggeredby;
		else
			return triggeredby[0];
	};



	RVS.F.updateLayerToggleActionWaits = function() {
		var _GL = RVS.SLIDER.staticSlideId!==undefined && RVS.SLIDER[RVS.SLIDER.staticSlideId]!==undefined ? RVS.SLIDER[RVS.SLIDER.staticSlideId].layers : undefined;
		for (var i in RVS.L) {
			if(!RVS.L.hasOwnProperty(i)) continue;
			if (RVS.L[i].actions !==undefined) {
				for (var j in RVS.L[i].actions.action)	{
					if(!RVS.L[i].actions.action.hasOwnProperty(j)) continue;
					if (RVS.L[i].actions.action[j].layer_target!==undefined && RVS.L[i].actions.action[j].action==="toggle_layer") {
						var tgt = RVS.L[i].actions.action[j].layer_target,
							static = _GL!==undefined && (""+tgt).indexOf("static-")>=0;
						tgt = static ? tgt.replace("static-","") : tgt;
						if ((static && _GL[tgt]!== undefined) || (!static &&RVS.L[tgt]!==undefined)) {
							var res = !(RVS.L[i].actions.action[j].toggle_layer_type==="visible");
							if (static) {
								_GL[tgt].timeline.frames.frame_1.timeline.actionTriggered = res;
								_GL[tgt].timeline.frames.frame_999.timeline.actionTriggered = true;
							} else {
								RVS.L[tgt].timeline.frames.frame_1.timeline.actionTriggered = res;
								RVS.L[tgt].timeline.frames.frame_999.timeline.actionTriggered = true;
							}
						 }
					}
				}
			}
		}
		RVS.F.updateAllLayerFrames();
	}


	/*
	BUILD LAYER ACTION LIST AND DEPENDENCIES
	*/
	RVS.F.initActionsOfLayers = function() {
		var l = RVS.L[RVS.selLayers[0]],
			ldw = jQuery('#layer_depending_wrap'),
			depends = RVS.F.layerFrameTriggeredBy({all:true,layerid:RVS.selLayers[0],frame:"any"}),
			header = '<i class="lwa_icon material-icons">'+RVS.F.getLayerIcon(l.linebreak ? 'linebreak' : l.type,l.subtype)+'</i><span class="lwa_layername">'+l.alias+'</span>';

		ldw[0].innerHTML = "";

		jQuery('#layer_with_action_wrap').removeClass("opendeps");

		if (depends.length>0) header += '<span class="drop_dependencies">'+RVS_LANG.triggeredby+'<i class="material-icons">arrow_drop_up</i></span>';

		for (var i in depends) {
			if(!depends.hasOwnProperty(i)) continue;
			if (depends[i].uid!==undefined && depends[i].action!==undefined)
				ldw.append('<li data-id="'+depends[i].uid+'" class="layer_depending_on"><i class="material-icons ldo_icon">'+depends[i].icon+'</i><span class="ldo_layername">'+depends[i].alias+(depends[i].slide!==RVS.S.slideId ? " ("+depends[i].slide+")" : "")+'</span><span class="ldo_actionname">'+RVS_LANG["layeraction_"+depends[i].action]+'</span></li>');
		}
		document.getElementById('add_action_to_layername').innerHTML = l.alias;
		document.getElementById('layer_with_action').innerHTML = header;
		RVS.F.updateEasyInputs({container:jQuery('#layer_width_action_inner_wrap'), path:RVS.S.slideId+".layers."+RVS.selLayers[0]+".", trigger:"init"});
		jQuery('#rbm_layer_action .tos2, #rbm_layer_action .slideinput, #rbm_layer_action .sliderinput').trigger('init');
		RVS.F.buildActionList();
	};



	// Update The Action Relations on all Triggering Layers based on the current Layer Id
	RVS.F.updateTriggeringActionRelations = function(lid) {
		var dependson = RVS.F.layerFrameTriggeredBy({all:true,layerid:lid,frame:"any"});
		for (var i in dependson) {
			if (!dependson.hasOwnProperty(i)) continue;
			if (dependson[i].uid!==undefined && dependson[i].action!==undefined)
			 	RVS.F.checkActionRelations(dependson[i].uid);
		}
	}

	//Update the Action Lists in the Layer (with LID Layer ID) to set unneeded actions Inactive
	RVS.F.checkActionRelations = function(lid) {
		var l = RVS.L[lid];
		if(l === undefined) return;
		l.actions.inactive_actions = l.actions.inactive_actions || [];
		var	merge = [...l.actions.action,...l.actions.inactive_actions],
			active=[],inactive=[];
		for (var i in merge) {
			if(!merge.hasOwnProperty(i)) continue;
			if (merge[i].layer_target==="backgroundvideo" || merge[i].layer_target==="firstvideo") if (merge[i].action==="toggle_layer") merge[i].action="toggle_video";
			var targetlayer = getActionTarget(merge[i]),
				ignore = false;
			if (targetlayer.targetid!=-1) {
				if ((RVS.SLIDER[targetlayer.slideid].layers[targetlayer.targetid]!==undefined) || (merge[i].layer_target==="backgroundvideo" || merge[i].layer_target==="firstvideo")) {
					// KEEP IT
				} else ignore = true;
			}

			if (!ignore) active.push(merge[i]); else inactive.push(merge[i]);
		}
		l.actions.action = active.slice(0);
		l.actions.inactive_actions = inactive.slice(0);

	}

	RVS.F.buildActionList = function() {
		var sla = jQuery('#selected_layer_actions'),
			l = RVS.L[RVS.selLayers[0]],
			newlist=[];

		RVS.F.checkActionRelations(RVS.selLayers[0]);
		jQuery('.actionDependent').removeClass("actionDependent");
		jQuery('.actionselected').removeClass("actionselected");
		// BUILD ACTION LIST
		sla[0].innerHTML = "";
		for (var i in l.actions.action) {
			if(!l.actions.action.hasOwnProperty(i)) continue;
			var li =  '<li class="single_layer_action '+(i==RVS.S.actionIdx ? "selected" : "")+'">',
				targetlayer = getActionTarget(l.actions.action[i]);

			if (targetlayer.targetid!=-1) {
				if (RVS.SLIDER[targetlayer.slideid].layers[targetlayer.targetid]!==undefined) {
					var static = targetlayer.slideid.indexOf('static') >= 0;
					li += '<i class="sla_icon material-icons">'+RVS.F.getLayerIcon(RVS.SLIDER[targetlayer.slideid].layers[targetlayer.targetid].linebreak ? 'linebreak' : RVS.SLIDER[targetlayer.slideid].layers[targetlayer.targetid].type,RVS.SLIDER[targetlayer.slideid].layers[targetlayer.targetid].subtype)+'</i><span class="sla_layername">'+RVS.SLIDER[targetlayer.slideid].layers[targetlayer.targetid].alias+(static ? '(S)':'') + '</span>';
					if (!static) RVS.H[targetlayer.targetid].w.addClass("actionDependent");
				}
				else
				if (l.actions.action[i].layer_target==="backgroundvideo" || l.actions.action[i].layer_target==="firstvideo")
					li += '<i class="sla_icon material-icons">videocam</i><span class="sla_layername">'+RVS_LANG[l.actions.action[i].layer_target]+'</span>';
				else
					li += '<i class="sla_icon material-icons">error_outline</i><span class="sla_layername">'+RVS_LANG.noLayersSelected+'</span>';
			} else
			if (RVS.LIB.ACTIONTYPES[l.actions.action[i].action]!==undefined)
				li += '<i class="sla_icon material-icons">'+RVS.LIB.ACTIONTYPES[l.actions.action[i].action].icon+'</i><span class="sla_layername"></span>';
			else
				li += '<i class="sla_icon material-icons">extension</i><span class="sla_layername"></span>';

			if (RVS.LIB.ACTIONTYPES[l.actions.action[i].action]!==undefined && RVS_LANG["layeraction_"+l.actions.action[i].action]!==undefined)
				li += '<span class="sla_actionname">'+RVS_LANG["layeraction_"+l.actions.action[i].action]+'</span>';
			else
				li += '<span class="sla_actionname">'+l.actions.action[i].action+'</span>';

			li += '<div class="single_layer_toolbar"><i class="material-icons duplicate_single_layer_action">content_copy</i><i class="material-icons delete_single_layer_action">delete_forever</i></div>';
			li += '</li>';
			sla.append(li);

 		}
 		//LAYER HAS NO ACTION YET
 		if (l.actions.action.length===0) {
 			jQuery('#layeraction_list').show();
 			jQuery('#selected_layer_actions').hide();
 			jQuery('#addactiontolayer').hide();
 		} else {
 			jQuery('#layeraction_list').hide();
 			jQuery('#selected_layer_actions').show();
 			jQuery('#addactiontolayer').show();
 		}
 		updateActionScrollbars();
	};

	function getSliderIdFromAlias(alias) {
		var found = false;
		for (var i in RVS.LIB.SLIDERS) if (RVS.LIB.SLIDERS.hasOwnProperty(i)) {
			if (found!==false) continue;
			if (RVS.LIB.SLIDERS[i].alias===alias) found = RVS.LIB.SLIDERS[i].id;
		}
		return found;
	}

	/*
	UPDATE THE LIST OF SLIDES AFTER SELECTING MODAL MODULE
	 */
	RVS.F.refreshModalSlides = function() {

		if(RVS.L[RVS.selLayers[0]].actions.action.length) {
			if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action=="open_modal" && RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].openmodal!==undefined) {
				var sid = getSliderIdFromAlias(RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].openmodal);
				RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].openmodalId = sid;
				if (sid===false) return;
				var op = '';
				RVS.F.ajaxRequest('get_slides_by_slider_id',{id:sid},function(response){
                    if (response.success) {
                        var slideCount=0;
                        for (var sid in response.slides) if (response.slides.hasOwnProperty(sid)) {
                            slideCount++;
                            op += '<option value="rs-'+response.slides[sid].id+'">#'+slideCount+" "+response.slides[sid].title+'</option>';
                        }
                    var gf = jQuery('#la_open_modalslide');
						gf[0].innerHTML = op;

						if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].modalslide!==undefined) gf[0].value = RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].modalslide;
						//UPDATE SELECT LIST
                        gf.ddTP({placeholder:"Select From List"});
						RVS.F.updateEasyInputs({container:jQuery('#rbm_layer_action'), path:RVS.S.slideId+".layers."+RVS.selLayers[0]+".", trigger:"init"});
                    }
				});

			}
		}
	}

	/*
	UPDATE THE FRAMES LIST AFTER SELECTING LAYER
	*/
	RVS.F.refreshFrameLists = function() {
		for (var i in xmn) if (xmn.hasOwnProperty(i)) {

            if (RVS.S.actionTrgtLayerId===undefined) return;
			var static = typeof RVS.S.actionTrgtLayerId === 'string' && RVS.S.actionTrgtLayerId.indexOf('static-')>=0,
				layerid = typeof RVS.S.actionTrgtLayerId === 'string' ? RVS.S.actionTrgtLayerId.replace('static-','') : RVS.S.actionTrgtLayerId,
                layer = static ? RVS.SLIDER[RVS.SLIDER.staticSlideId].layers[layerid] : RVS.L[RVS.S.actionTrgtLayerId],
                op ="",
                gf=jQuery('#la_gotoframe'+xmn[i]);

            if (layer===undefined || layer===NaN) return;
            var frameorder = layer.timeline.frameOrder,
                frames = layer.timeline.frames;
            op += '<option value="frame_1">'+frames.frame_1.alias+' ( '+RVS_LANG.frstframe+')'+'</option>';
			for (var frameid in frameorder) {
				if(!frameorder.hasOwnProperty(frameid)) continue;
				var frame = frameorder[frameid].id;
				if (frame!=="frame_0" && frame!=="frame_1" && frame!=="frame_999")
					op += '<option value="'+frame+'">'+frames[frame].alias+' ('+frameid+'.Frame )'+'</option>';
			}
			op += '<option value="frame_999">'+frames.frame_999.alias+' ( '+RVS_LANG.lastframe+')'+'</option>';
			gf[0].innerHTML = op;

			//CHECK IF FRAME EXISTS, AND SELECT
			if (RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx]["gotoframe"+(xmn[i]==="X" ? "" : xmn[i])] && frames[RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx]["gotoframe"+(xmn[i]==="X" ? "" : xmn[i])]]!==undefined)
				gf[0].value = RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx]["gotoframe"+(xmn[i]==="X" ? "" : xmn[i])];
			else {
				RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx]["gotoframe"+(xmn[i]==="X" ? "" : xmn[i])] = "frame_1";
				gf[0].value = RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx]["gotoframe"+(xmn[i]==="X" ? "" : xmn[i])];
			}


			//UPDATE SELECT LIST
			gf.ddTP({placeholder:"Select From List"});

			//UPDATE PLAY FRAME ONLY INPUT
			RVS.F.updatePlayFrameXOnlyOnAction(null,xmn[i]);
		}

	};

	RVS.F.updatePlayFrameXOnlyOnAction = function(a,b) {

		b = b===undefined ? "X" : typeof b ==="object" ? b.eventparam : b;

		if (RVS.S.actionTrgtLayerId===undefined) return;

		var static = typeof RVS.S.actionTrgtLayerId === 'string' && RVS.S.actionTrgtLayerId.indexOf('static-') >= 0,
			layerid = typeof RVS.S.actionTrgtLayerId === 'string' ? RVS.S.actionTrgtLayerId.replace('static-', '') : RVS.S.actionTrgtLayerId,
			layer = static ? RVS.SLIDER[RVS.SLIDER.staticSlideId].layers[layerid] : RVS.L[RVS.S.actionTrgtLayerId];

		var inp = document.getElementById('overtake_frame'+b+'_control'),
			frames = layer.timeline.frames,
			gf = RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx]["gotoframe"+(b==="N" || b==="M" ? b : "")];
		if (gf && frames[gf]!==undefined) {
			inp.dataset.r = "timeline.frames."+gf+".timeline.actionTriggered";
			inp.checked = layer.timeline.frames[gf].timeline.actionTriggered;
			RVS.F.turnOnOffVisUpdate({input:inp});
		}
	};

	RVS.F.upadteLayerTargetDropDowns = function(_) {
		// SET UP DROPDOWN BOXES
		var lalt = jQuery('#la_layer_target'),
			showmedia = RVS.LIB.ACTIONTYPES[_.action]!==undefined ? RVS.LIB.ACTIONTYPES[_.action].media : false;

		lalt[0].innerHTML="";

		if (showmedia) {
			lalt[0].innerHTML += '<option data-icon="videocam" value="backgroundvideo">'+RVS_LANG.backgroundvideo+'</option>';
			lalt[0].innerHTML += '<option data-icon="videocam" value="firstvideo">'+RVS_LANG.videoactiveslide+'</option>';
		}
		for (var l in RVS.L) {
			if(!RVS.L.hasOwnProperty(l)) continue;
			if (RVS.L[l].type!==undefined &&RVS.L[l].type!=="zone" && (!showmedia || (RVS.L[l].type==="video" || RVS.L[l].type==="audio")))
				lalt[0].innerHTML += '<option data-icon="'+RVS.F.getLayerIcon(RVS.L[l].linebreak ? 'linebreak' : RVS.L[l].type)+'" value="'+RVS.L[l].uid+'">'+RVS.L[l].alias+'</option>';
		}
		if ((""+RVS.S.slideId).indexOf("static_")===-1 && RVS.SLIDER.staticSlideId!==undefined && RVS.SLIDER[RVS.SLIDER.staticSlideId]!==undefined) {
			lalt[0].innerHTML += '<optgroup label="'+RVS_LANG.globalLayers+'">';
			var _GL = RVS.SLIDER[RVS.SLIDER.staticSlideId].layers;
			for (var l in _GL) {
				if(!_GL.hasOwnProperty(l)) continue;
				if (_GL[l].type!==undefined && _GL[l].type!=="zone" && (!showmedia || (_GL[l].type==="video" || _GL[l].type==="audio")))
					lalt[0].innerHTML += '<option data-icon="'+RVS.F.getLayerIcon(_GL[l].linebreak ? 'linebreak' : _GL[l].type)+'" value="static-'+_GL[l].uid+'">'+_GL[l].alias+'</option>';
			}
			lalt[0].innerHTML +=  '</optgroup>';
		}

		lalt.val(_.targetid).ddTP('rebuild',{

			pre: '<span><i class="icon_in_dropdown material-icons">#data#</i></span>',
			preData : 'icon'
		});

		if (_.targetid===undefined || RVS.L[_.targetid]===undefined) return;
		if (jQuery.inArray(RVS.L[_.targetid].type,["group","row","column"])>=0 && jQuery.inArray(_.action,["start_in","start_out","start_frame","next_frame","prev_frame","toggle_layer","toggle_frames"])>=0) {

			jQuery('#la_settings_childrentimelines').show();
		}
		else
			jQuery('#la_settings_childrentimelines').hide();
	};

	RVS.F.updateLinkTypes = function(_) {
		//disable_action_ongroups
		var lalt = jQuery('#la_link_type');
		lalt[0].innerHTML = "";
		lalt.append('<option value="jquery">'+RVS_LANG.jquerytriggered+'</option>');
		if (jQuery.inArray(RVS.L[RVS.selLayers[0]].type,["group","column","row"])==-1) lalt.append('<option value="a">'+RVS_LANG.atriggered+'</option>');
		lalt.val(_.linktype).ddTP('change').ddTP({});
	};



	/*
	CREATE SINGLE ACTION GROUPS IN ACCORDION FORM
	*/
	RVS.F.createActionGroup = function(obj) {
		let GA = document.getElementById(obj.id),
			exist = GA!==null;

		obj.title = RVS_LANG[obj.id]===undefined ? obj.title ? obj.title : obj.id : RVS_LANG[obj.id];

		if (!exist) {
			GA = RVS.F.cE({cN:'lal_group', id:obj.id});
			let header = RVS.F.cE({cN:'lal_group_header'});
			header.textContent = obj.title;
			let fi = RVS.F.cI({c:obj.icon});
			header.prepend(fi);
			header.appendChild(RVS.F.cI({cN:'accordiondrop',c:'arrow_drop_down'}));
			GA.appendChild(header);
		}


		//ADD ACTIONS TO THE GROUP
		for (var i in obj.actions) {

			if(!obj.actions.hasOwnProperty(i)) continue;
			let a = obj.actions[i];

			if (a.alias!==undefined) RVS_LANG["layeraction_"+a.val] = a.alias;

			let	title = a.title!==undefined ? a.title : RVS_LANG['layeraction_'+a.val]===undefined ? a.val : RVS_LANG['layeraction_'+a.val];
			a.inputs = a.inputs===undefined ? "" : a.inputs;
			a.inputs = 	(a.layerTarget===true ? "#la_settings_layertarget"+ (a.inputs.length>0 ? ", "+a.inputs:"") : a.inputs);
			let lap = RVS.F.cE({cN:'lal_group_member',id:'layeraction_picker_'+a.val,ds:{val:a.val, inputs:a.inputs}});
			let lapi = RVS.F.cI({c:obj.icon});
			lap.prepend(RVS.F.cI({c:obj.icon}));
			lap.textContent = title;
			GA.appendChild(lap);
			if (a.layerTarget) RVS.LIB.ACTION_WITH_TRGT.push(a.val);
			RVS.LIB.ACTIONTYPES[a.val] = {inputs:a.inputs, name:title, icon:obj.icon, layerTarget:a.layerTarget, media:a.media};
		}

		//ADD GROUP TO THE ACTION CONTAINER IF NOT YET ADDED
		if (!exist) document.getElementById('layeraction_list').appendChild(GA);
	};

	/*
	UPDATE SLIDE LIST
	*/
	RVS.F.updateSlideList = function(_) {
		var sel = jQuery('#la_jump_to_slide');
		var opts = '<option value="first">'+RVS_LANG.firstslide+'</option>';
		opts += '<option value="last">'+RVS_LANG.lastslide+'</option>';
		opts += '<option value="random">'+RVS_LANG.randomslide+'</option>';
		if (_.targetid === "next") opts += '<option value="next">'+RVS_LANG.nextslide+'</option>';
		if (_.targetid === "previous") opts += '<option value="previous">'+RVS_LANG.previousslide+'</option>';


		var slideCount = 0;
		for (var i in RVS.SLIDER.slideIDs) {
			if(!RVS.SLIDER.slideIDs.hasOwnProperty(i)) continue;
			var id = RVS.SLIDER.slideIDs[i]+"";
			if (id.indexOf("static_")===-1)  {
				slideCount++;
				opts += '<option value='+id+'>#'+slideCount+' '+RVS.SLIDER[id].slide.title+'</option>';
			}
		}

		sel[0].innerHTML = opts;
		sel.val(_.targetid).ddTP('change').ddTP({});

	};



	/********************************
			INIT ACTIONS
	*********************************/
	function initActions() {
		// LINK ACTIONS
		RVS.F.createActionGroup({icon:"link", id:"layeraction_group_link", actions:[
			{val:"menu", inputs:"#la_settings_link_menu, #la_settings_link,#la_settings_scroll_under"},
			{val:"link", inputs:"#la_settings_link_url,#la_settings_link,#la_settings_link_type"},
			{val:"callback", inputs:"#la_settings_callback"},
			{val:"getAccelerationPermission", inputs:"#la_settings_getAccelerationPermissionk"},
			{val:"scrollto", inputs:"#la_settings_scroll_to,#la_settings_scroll_under"},
			{val:"scroll_under", inputs:"#la_settings_scroll_under"}]});

		// SLIDE ACTIONS
		RVS.F.createActionGroup({icon:"code", id:"layeraction_group_slide", actions:[{val:"jumpto", inputs:"#la_settings_jumpto"},  {val:"next"}, {val:"prev"},{val:"pause"},{val:"resume"},{val:"toggle_slider"},{val:"close_modal"},{val:"open_modal", inputs:"#la_settings_modal"}]});


		// LAYER ACTIONS
		RVS.F.createActionGroup({icon:"layers", id:"layeraction_group_layer", actions:[
			{val:"start_in", inputs:"#la_settings_layer_actions, #la_settings_layer_actions_in, #la_settings_childrentimelines", layerTarget:true},
			{val:"start_out", inputs:"#la_settings_layer_actions, #la_settings_layer_actions_out, #la_settings_childrentimelines", layerTarget:true},
			{val:"start_frame", inputs:"#la_settings_layer_actions, #la_settings_layer_actions_frame, #la_settings_childrentimelines", layerTarget:true},
			{val:"next_frame", inputs:"#la_settings_layer_actions, #la_settings_childrentimelines", layerTarget:true},
			{val:"prev_frame", inputs:"#la_settings_layer_actions, #la_settings_childrentimelines", layerTarget:true},
			{val:"toggle_layer", inputs:"#la_settings_layer_actions, #la_settings_layer_toggle_actions, #la_settings_childrentimelines", layerTarget:true},
			{val:"toggle_frames", inputs:"#la_settings_layer_actions, #la_settings_layer_actions_frameXY,  #la_settings_childrentimelines", layerTarget:true}
		]});

		// MEDIA ACTIONS
		RVS.F.createActionGroup({icon:"videocam", id:"layeraction_group_media", actions:[
			{val:"start_video", layerTarget:true,media:true},
			{val:"stop_video", layerTarget:true,media:true},
			{val:"toggle_video", layerTarget:true,media:true},
			{val:"mute_video", layerTarget:true,media:true},
			{val:"unmute_video", layerTarget:true,media:true},
			{val:"toggle_mute_video", layerTarget:true,media:true},
			{val:"toggle_global_mute_video"}
		]});

		// FULLSCREEN ACTIONS
		RVS.F.createActionGroup({icon:"fullscreen", id:"layeraction_group_fullscreen", actions:[{val:"togglefullscreen"}, {val:"gofullscreen"}, {val:"exitfullscreen"}]});

		// ADVANCED ACTIONS
		RVS.F.createActionGroup({icon:"layers", id:"layeraction_group_layer", actions:[{val:"simulate_click", layerTarget:true}, {val:"toggle_class", inputs:"#la_settings_class", layerTarget:true}]});

		RVS.DOC.trigger("extendLayerActionGroups");
	}

	/********************************
	INIT LOCAL INPUT BOX FUNCTIONS
	*********************************/
	function initLocalInputBoxes() {

	}

	/********************************
	 	SOME INTERNAL FUNCTIONS
	*********************************/
	function getActionTarget(action) {
		var result = -1;
		if (jQuery.inArray(action.action, RVS.LIB.ACTION_WITH_TRGT) != -1 && action.layer_target !== undefined) result = parseInt(action.layer_target.toString().replace('static-',''), 0);
		return { slideid: action !== undefined && action.layer_target !== undefined && action.layer_target.toString().indexOf('static') >= 0 ? RVS.SLIDER.staticSlideId : RVS.S.slideId, targetid: result==-1 || isNaN(result) ? -1 : result};
	}

	function updateActionScrollbars() {
		jQuery('#layeraction_list').RSScroll({
			wheelPropagation:false,
			suppressScrollX:true,
			minScrollbarLength:100
		});
		jQuery('#layeractions_overview_innerwrap').RSScroll({
			wheelPropagation:false,
			suppressScrollX:true,
			minScrollbarLength:100
		});
	}

	function addNewAction(action) {
		var lastaction = RVS.L[RVS.selLayers[0]].actions.action.length,
			newactions = RVS.F.safeExtend(true,{},RVS.L[RVS.selLayers[0]].actions);
		newactions.action.push({action:action,tooltip_event:"click", link_help_in:"keep", link_open_in:"_self", link_follow:"nofollow"});
		RVS.F.updateSliderObj({path:RVS.S.slideId+'.layers.'+RVS.selLayers[0]+'.actions',val:newactions});

		RVS.S.actionIdx = lastaction;
		RVS.F.buildActionList();
		RVS.F.selectLayerAction();
		RVS.F.refreshFrameLists();
		RVS.F.refreshModalSlides();
		RVS.DOC.trigger('layer_action_selected');
	}

	/*
	INIT CUSTOM EVENT LISTENERS FOR TRIGGERING FUNCTIONS
	*/
	function initLocalListeners() {

		// OPEN LAYER ACTIONS
		var doc = RVS.DOC.on('openLayerActions',RVS.F.openLayerActions);

		RVS.DOC.on('updatePlayFrameXOnlyOnAction',RVS.F.updatePlayFrameXOnlyOnAction);

		// SHOW HIDE DEPENDENCIES ON LAYER ACTION
		RVS.DOC.on('click','.drop_dependencies',function() {
			jQuery('#layer_with_action_wrap').toggleClass("opendeps");
		});

		//CLICK ON CLOSE IN DIFFERENT STATES
		RVS.DOC.on('click','#rbm_layer_action .rbm_close',function() {
			if (jQuery('#rbm_layer_action').hasClass("inpickermode")) {
				jQuery('#rbm_layer_action').removeClass("inpickermode");
				jQuery('#layeraction_list').hide();
				jQuery('#layer_action_type').show();
			} else {
				jQuery('.actionDependent').removeClass("actionDependent");
				RVS.F.RSDialog.close();
			}
			for (var i in RVS.L) if (RVS.L.hasOwnProperty(i)) {
				if (RVS.L[i].actions && RVS.L[i].actions.action.length>0)
					jQuery('#tllayerlist_element_'+RVS.S.slideId+'_'+RVS.L[i].uid).addClass("actionmarked");
				else
					jQuery('#tllayerlist_element_'+RVS.S.slideId+'_'+RVS.L[i].uid).removeClass("actionmarked");
			}
			RVS.F.updateAllLayerFrames();
		});

		// CREATE NEW LAYER ACTION
		RVS.DOC.on('click','#addactiontolayer',function() {
			addNewAction("link");
			return false;
		});

		//Clean up the broken Actions
		RVS.F.cleanUpAllActions = function() {
			for (var i in RVS.L) if (RVS.L.hasOwnProperty(i)) cleanUpActions(i);
		}

		RVS.F.cleanUpActions = function(i) {
			var cleanActions = [];
			if (RVS.L[i].actions!==undefined) {
				for (var j in RVS.L[i].actions.action) {
					if (RVS.L[i].actions.action[j].migrateMute==undefined && RVS.L[i].actions.action[j].rsColorPicker==undefined && RVS.L[i].actions.action[j].get==undefined)
					cleanActions.push(RVS.L[i].actions.action[j]);
				}
				RVS.L[i].actions = cleanActions;
			}
		}

		RVS.F.updateDuplicatedLayerActionDependencies = function(newid, oldid) {
			var olddeps = RVS.F.layerFrameTriggeredBy({all:true,layerid:oldid,frame:"any"}),
				action, exttmp, extend=[],i,j,olddepsuids=[];

			for (i in olddeps) {
				if (!olddeps.hasOwnProperty(i) || RVS.L[olddeps[i].uid]===undefined) continue;
				if (jQuery.inArray(""+olddeps[i].uid,olddepsuids)===-1) olddepsuids.push(""+olddeps[i].uid);
			}
			for (i in olddepsuids) {
				extend=[];
				for (j in RVS.L[olddepsuids[i]].actions.action) {

					if (!RVS.L[olddepsuids[i]].actions.action.hasOwnProperty(j) ||
						jQuery.inArray(RVS.L[olddepsuids[i]].actions.action[j].action, RVS.LIB.ACTION_WITH_TRGT) == -1 ||
						(""+RVS.L[olddepsuids[i]].actions.action[j].layer_target) !== (""+oldid)) continue;
					else {
						exttmp = RVS.F.safeExtend(true,{},RVS.L[olddepsuids[i]].actions.action[j]);
						exttmp.layer_target = ""+newid;
						extend.push(exttmp);
					}
				}
				if (extend.length>0)  for ( j in extend) if(extend.hasOwnProperty(i)) {
					RVS.L[olddeps[i].uid].actions.action.push(RVS.F.safeExtend(true,{},extend[j]));
				}
			}

		}

		// DUPLICATE NEW LAYER ACTION
		RVS.DOC.on('click','.duplicate_single_layer_action',function() {
			var li = jQuery(this).closest('.single_layer_action'),
				lastaction = RVS.L[RVS.selLayers[0]].actions.action.length,
				newactions = RVS.F.safeExtend(true,{},RVS.L[RVS.selLayers[0]].actions);
			newactions.action.push(RVS.L[RVS.selLayers[0]].actions.action[li.index()]);
			RVS.F.updateSliderObj({path:RVS.S.slideId+'.layers.'+RVS.selLayers[0]+'.actions',val:newactions});
			RVS.S.actionIdx = lastaction;
			setTimeout(function() {
				RVS.F.buildActionList();
				RVS.F.selectLayerAction();
				RVS.F.refreshFrameLists();
				RVS.F.refreshModalSlides();
			},50);
			return false;
		});

		// DELETE LAYER ACTION
		RVS.DOC.on('click','.delete_single_layer_action',function() {
			var li = jQuery(this).closest('.single_layer_action'),
				/* lastaction = RVS.L[RVS.selLayers[0]].actions.action.length-1, */
				newactions = RVS.F.safeExtend(true,{},RVS.L[RVS.selLayers[0]].actions);


			newactions.action.splice(li.index(),1);
			RVS.S.actionIdx = 0;
			RVS.F.updateSliderObj({path:RVS.S.slideId+'.layers.'+RVS.selLayers[0]+'.actions',val:newactions});
			setTimeout(function() {
				RVS.F.buildActionList();
				RVS.F.selectLayerAction();
				RVS.F.refreshFrameLists();
				RVS.F.refreshModalSlides();
				RVS.DOC.trigger('layer_action_selected');
			},50);
			return false;
		});

		// PICK LAYER ACTION
		RVS.DOC.on('click','#layer_action_type',function() {
			jQuery('#rbm_layer_action').addClass("inpickermode");
			jQuery('#layeraction_list').show();
			jQuery(this).hide();
			jQuery('#layeraction_list .lal_group_member').removeClass("selected");
			jQuery('#layeraction_picker_'+RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx].action).addClass("selected");
		});

		// PICK A NEW LAYER ACTION
		RVS.DOC.on('click','.lal_group_member',function() {
			jQuery('#rbm_layer_action').removeClass("inpickermode");
			jQuery('#layer_action_type').show();
			if (RVS.S.actionIdx===undefined || RVS.L[RVS.selLayers[0]].actions.action[RVS.S.actionIdx]===undefined) {
				addNewAction(this.dataset.val);
				jQuery('#action_inputs .la_settings').hide();
				jQuery(RVS.LIB.ACTIONTYPES[this.dataset.val].inputs).show();
				jQuery('#layeraction_list').hide();
			} else {
				RVS.F.updateSliderObj({path:RVS.S.slideId+'.layers.'+RVS.selLayers[0]+'.actions.action.'+RVS.S.actionIdx+'.action',val:this.dataset.val});
				jQuery('#action_inputs .la_settings').hide();
				jQuery(RVS.LIB.ACTIONTYPES[this.dataset.val].inputs).show();
				jQuery('#layeraction_list').hide();
				RVS.F.buildActionList();
				RVS.F.selectLayerAction();
				RVS.F.refreshFrameLists();
				RVS.F.refreshModalSlides();
			}
			RVS.DOC.trigger('layer_action_selected');

		});

		// CLICK ON A SINGLE LAYER ACTION
		RVS.DOC.on('click','.single_layer_action', function(e) {
			RVS.S.actionIdx = jQuery(this).index();
			jQuery('.single_layer_action').removeClass("selected");
			jQuery(this).addClass("selected");
			RVS.F.selectLayerAction();
			RVS.F.refreshFrameLists();
			RVS.F.refreshModalSlides();
			RVS.DOC.trigger('layer_action_selected');
		});

		// CLICK ON ACCORDION, SHOUD OPEN/CLOSE THE ACTION ACCORDIONS
		RVS.DOC.on('click','.lal_group_header',function() {
			var group = jQuery(this).closest('.lal_group');
			group.toggleClass("closed");
		});

		RVS.DOC.on('refreshActionView',function() {
			RVS.F.buildActionList();
			RVS.F.selectLayerAction();
			RVS.F.refreshFrameLists();
			RVS.F.refreshModalSlides();
		});

		RVS.DOC.on('refreshSlideLists',function() {
			RVS.F.refreshModalSlides();
		});

		RVS.DOC.on('refreshLayerToggleState',function() {

			var static = typeof RVS.S.actionTrgtLayerId === 'string' && RVS.S.actionTrgtLayerId.indexOf('static-') >= 0,
				layerid = typeof RVS.S.actionTrgtLayerId === 'string' ? RVS.S.actionTrgtLayerId.replace('static-', '') : RVS.S.actionTrgtLayerId,
				layer = static ? RVS.SLIDER[RVS.SLIDER.staticSlideId].layers[layerid] : RVS.L[RVS.S.actionTrgtLayerId];

			if (jQuery('#toggle_layer_type').val()=="visible")
                layer.timeline.frames.frame_1.timeline.actionTriggered = false;
            else
                layer.timeline.frames.frame_1.timeline.actionTriggered = true;

			RVS.F.buildActionList();
			RVS.F.selectLayerAction();
			RVS.F.refreshFrameLists();
			RVS.F.refreshModalSlides();
		});


	}

})();
