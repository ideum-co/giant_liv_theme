/********************************************
 * REVOLUTION  EXTENSION - CAROUSEL
 * @date:  06.10.2022
 * @requires rs6.main.js
 * @author ThemePunch
*********************************************/

(function($) {
    "use strict";
    var version="6.6.0";
    jQuery.fn.revolution = jQuery.fn.revolution || {};
    var _R = jQuery.fn.revolution;

        ///////////////////////////////////////////
        // 	EXTENDED FUNCTIONS AVAILABLE GLOBAL  //
        ///////////////////////////////////////////
    jQuery.extend(true,_R, {

        // CALCULATE CAROUSEL POSITIONS
        prepareCarousel : function(id,direction,speed, skip) {
            if (id===undefined) return;
            var _ = _R[id].carousel;

            _.slidesWithRowAdjustions = {};
            direction = _.lastdirection = dircheck(direction,_.lastdirection);
            _R.setCarouselDefaults(id, undefined, skip);

            _R.organiseCarousel(id,"right",true,false,false);

            // if (_.focusedAfterAnimation === _.focused) return;
            if (_.swipeTo===undefined || !_R.isNumeric(_.swipeTo)) {
                _R.swipeAnimate({id:id,to:0,direction:direction,speed:0});
            } else if (speed!==undefined)
                _R.swipeAnimate({id:id,to:_.swipeTo, distance: _.swipeToDistance, direction:direction,fix:true,speed:speed});
            else _R.swipeAnimate({id:id,to:_.swipeTo, distance: _.swipeToDistance, direction:direction,fix:true});

            if (_R[id].sliderType==="carousel" && !_.fadein) {
                tpGS.gsap.to(_R[id].canvas,1,{scale:1,opacity:1});
                _.fadein=true;
            }
        },

        setupCarousel: function(id){

            var _ = _R[id].carousel;

            if(_.orientation=="v"){
                _.length = 'height';
                _.translate = 'y';
                _.slide_dims = 'slide_heights';
                _.deltaT = 'deltaY';
                _.sliderLength = 'sliderHeight';
                _.slide_length = 'slide_height';
                _.wraplength = 'wrapheight';
                _.align = _.vertical_align === '0%' ? 'start' : _.vertical_align === '50%' ? 'center' : 'end';
                if(_.snap && !_.justify && !_.infinity) _.forceBAlign = true;
            } else {
                _.length = 'width';
                _.translate = 'x';
                _.slide_dims = 'slide_widths';
                _.deltaT = 'deltaX';
                _.sliderLength = 'sliderWidth';
                _.slide_length = 'slide_width';
                _.wraplength = 'wrapwidth';
                _.align = _.horizontal_align === 'left' ? 'start' : _.horizontal_align === 'center' ? 'center' : 'end';
            }

            _[_.sliderLength] = _R[id].canv[_.length];
            _.proxy = document.createElement('div');
            _.follower = document.createElement('div');
            _.slideamount = _R[id].slideamount;

            if(!_.infinity && !_.snap)_R[id].carousel.align = 'start';
            initCarArrays(id);
            updateEdgeCalculation(id, _.align);

            _.inited = true;

            _.lerpHandler = _R.carLerpHandler.bind(this, id);

            if(_.animInList === undefined) _.animInList = [];

            _.draggableObj = {
                trigger: _R[id].c[0],
                type: _.translate,
                edgeResistance: 0.5,
                zIndexBoost: false,
                cursor: 'grab',
                activeCursor: 'grabbing',
                allowContextMenu: true,
                inertia: true,
                throwResistance: 8000,
                onPress: function(e){
                    if(_R.closestClass(e.target, 'rs-nav-element')) {
                        _.draggable.endDrag();
                        _.draggable.disable();
                    } else _R[id].c.trigger('stoptimer');

                    _.focusedOnPress = _.focused;
                    _.isPressed = true;
                    if(_.tween && _.tween.kill){
                        _.tween.kill();
                        delete _.tween;
                    }
                },
                onClick: function(e){
                    if(_R.closestClass(e.target, 'rs-nav-element') || _R.closestClass(e.target, 'rs-waction')) return;
                    if(!_.draggable.enabled()) return;
                    if(_R[id].carousel.stopOnClick === false) _R[id].c.trigger('starttimer');
                },
                onDragStart: function(){
                    _.lerpSpeed = 0.1;
                    if(!_.lerp) _.lerp = requestAnimationFrame(_.lerpHandler);
                    if(_R.ISM && _.forceBAlign) {
                        if(this.getDirection() === "up" && _.focused == _.slideamount -1 ||
                        this.getDirection() === "down" && _.focused == 0) _.forceScroll = true;
                        else _.forceScroll = false;
                    }
                },
                snap: function(v){
                    _R.getLastPos(id);
                    if(_.forceScroll && _.forceBAlign) {
                        if(this.getDirection() === "up") _R.document.scrollTop(_R[id].cpar.offset().top + _R[id].module.height);
                        else _R.document.scrollTop(_R.document.scrollTop() - (window.innerHeight - _R[id].cpar[0].getBoundingClientRect().top));
                        return _.focused == _.slideamount -1 && !_.infinity ? (_[_.wraplength] - _.totalWidth) : _.lastPos;
                    }
                    _R.calculateSnap(id, v);
                    _.isPressed = false;
                    return v;
                },
            }
            _.draggable = tpGS.draggable.create(_.proxy, _.draggableObj)[0];

            _R[id].c.one('revolution.slide.onchange', function (){triggerCarouselChange(id);});
        },

        positionCarousel: function(id){
            var _ = _R[id].carousel;
            _[_.sliderLength] = _R[id].canv[_.length];
            if(_.draggable.isPressed) return;
            initCarArrays(id);
            if ((_R.ISM && _R[id].navigation.touch.mobileCarousel) || (_R.ISM!==true && _R[id].navigation.touch.desktopCarousel)) {
                _.draggable.vars.cursor = "grab";
                _.draggable.enable();
            }
            else {
                _.draggable.vars.cursor = "pointer";
                _.draggable.disable();
            }

            if(_R[id].carousel.justify){
                _.wrapperWidth = 0;
                for(var i = 0; i < _R[id].carousel[_.slide_dims].length; i++){
                    _.wrapperWidth += _R[id].carousel[_.slide_dims][i];
                }
            } else{
                _.wrapperWidth = _.slide_width * _R[id].slides.length;
            }
            if(_.focused === undefined) _.focused = 0;
            _.activeSlide = _.focused;

            for(var i = 0; i < _.arr.length; i++){
                _R.updateSlideWidth(id, i);
            }

            var totalWidth = 0;

            _.lastWrapwidth = _.wrapwidth;
            _.lastWrapheight = _.wrapheight;
            // spacing calculation
            var startOffset;
            var tempOffset;
            if(_R[id].carousel.align === 'start'){
                startOffset = 0;
            }else if(_R[id].carousel.align === 'center') {
                startOffset = (_[_.wraplength] - _.arr[_.focused][_.length])/2;
            }else {
                startOffset = (_[_.wraplength] - _.arr[_.focused][_.length]);
            }
            tempOffset = startOffset;
            if(!_.infinity && _.orientation === "v" && _.focused == _.slideamount - 1) {
                startOffset = (_[_.wraplength] - _.arr[_.focused][_.length]);
            }
            tpGS.gsap.set([_.proxy, _.follower], {x: startOffset, y: startOffset});

            if(_.infinity){
                // adds space to next slides
                for(var i = _.focused; i < _.arr.length; i++){
                    if(i !== _.focused) totalWidth += _.space;

                    if(_.orientation === "h") tpGS.gsap.set(_.arr[i].elem, {x: totalWidth + startOffset});
                    else tpGS.gsap.set(_.arr[i].elem, {y: totalWidth + startOffset});
                    _.arr[i].posX = totalWidth + startOffset;
                    _.arr[i][_.translate] = totalWidth + startOffset;
                    totalWidth += _.arr[i][_.length];
                }

                var lastPosX = startOffset;

                // adds space to previous slides
                for(var i = _.focused - 1; i >= 0; i--){
                    lastPosX -= _.arr[i][_.length] + _.space;
                    _.arr[i].posX = lastPosX;
                    _.arr[i][_.translate] = lastPosX;
                    if(_.orientation === "h") tpGS.gsap.set(_.arr[i].elem, {x: lastPosX});
                    else tpGS.gsap.set(_.arr[i].elem, {y: lastPosX});
                    totalWidth += _.arr[i][_.length] + _.space;
                }
                tpGS.gsap.set([_.proxy, _.follower], {x: _.arr[_.focused].posX, y: _.arr[_.focused].posX});
            } else{
                var shift = 0;
                for(var i = 0; i < _.arr.length; i++){
                    if(i>0)totalWidth += _.space;
                    if(i == _.focused) shift += totalWidth;
                    _.arr[i].posX = totalWidth + startOffset;
                    _.arr[i][_.translate] = totalWidth + startOffset;
                    if(_.orientation === "h") tpGS.gsap.set(_.arr[i].elem, {x: totalWidth + startOffset});
                    else tpGS.gsap.set(_.arr[i].elem, {y: totalWidth + startOffset});
                    totalWidth += _.arr[i][_.length];
                }
                tpGS.gsap.set([_.proxy, _.follower], {x: startOffset - shift, y: startOffset - shift});
                if(_.orientation === "h") tpGS.gsap.set([_R[id].slides], {x:  "-=" + shift});
                else tpGS.gsap.set([_R[id].slides], {y:  "-=" + shift});

                for(var i = 0; i < _.arr.length; i++){
                    _.arr[i].posX -= shift;
                    _.arr[i][_.translate] -= shift;
                }

            }
            _.startOffset = tempOffset;
            _R.swapCarouselSlides(id, true);
            if(_.infinity) {
                _.draggable.applyBounds({
                    minX: -Infinity,
                    maxX: Infinity
                });
            } else if(!_.infinity && _.snap){
                _.draggable.applyBounds({
                    minX: -(_.startOffset + totalWidth),
                    maxX: _.startOffset
                });
            } else if(!_.infinity && !_.snap){
                _.draggable.applyBounds({
                    minX: (_.wrapwidth - totalWidth),
                    maxX: 0
                });
            }
            _.lastActiveSlide = _.activeSlide;
            _.totalWidth = totalWidth;
            _.lastTotalWidth = totalWidth;
            if(_.spin !== 'off'){
                var w = _[_.slide_length];

                var base = w/2;
                _.spinAngle = Math.max(Math.min(_.spinAngle, 360/_.arr.length), -360/_.arr.length);

                var hype = base/Math.sin((_.spinAngle/2) * Math.PI/180)
                _.spinR = (Math.sqrt(hype * hype - base * base)  + _.space ) * Math.sign(_.spinAngle);

                if(_.spin === '2d' && _.orientation === 'h') _.spinR += (_.spinAngle <= 0 ? 0 : 1) * (_R[id].sliderLayout === 'fullscreen' ? _.wrapheight : _.slide_height);
                else if(_.spin === '2d') _.spinR += (_.spinAngle <= 0 ? 0 : 1) * (_R[id].sliderLayout === 'fullscreen' ? _.wrapwidth : _.slide_width);
            }

            for(var i = 0; i < _.trackArr.length; i++){
                for(var j = 0; j < _.arr.length; j++){
                    if(_.trackArr[i].elem === _.arr[j].elem){
                        _.trackArr[i].width = _.arr[j].width;
                        _.trackArr[i].height = _.arr[j].height;
                    }
                }
            }
            _.oldfocused = _.focused;
            _R.applyDistanceEffect(id);
        },

        updateSlideWidth: function(id, index){
            var _ = _R[id].carousel;
            if(_.justify){
                for(var i = 0; i < _R[id].slides.length; i++){
                    if(_R[id].slides[i] === _.arr[index].elem){
                        _.arr[index][_.length] = _[_.slide_dims][i];
                    }
                }
            } else {
                _.arr[index][_.length] = _[_.slide_length];
            }
        },
        swapCarouselSlides: function(id){
            var _ = _R[id].carousel;
            var recheck = true;
            if(!_.infinity || _.totalWidth < _[_.wraplength]) return;
            // swap slide positions if visible canvas needs slides
            var lastSwap;
            while(recheck){

                var swapToLeft = parseFloat(_.arr[0][_.translate]) > 0 || (_.arr[0].progress !== undefined && _.arr[0].progress <= 1 && !(_.arr[_.arr.length - 1].progress <= 1));
                var swapToRight = parseFloat(_.arr[_.arr.length - 1][_.translate]) < _[_.wraplength] - _.arr[_.arr.length - 1][_.length] ||
                (_.arr[_.arr.length - 1].progress !== undefined && _.arr[_.arr.length - 1].progress <= 1 && !(_.arr[0].progress <= 1));

                if(swapToLeft){
                    var lastPosX = parseFloat(_.arr[0][_.translate]) - _.space;
                    var val = _.arr.pop();
                    _.arr.unshift(val);

                    if(lastSwap === val) recheck = false;
                    lastSwap = val;
                    _.arr[0].posX = _.arr[0][_.translate] = lastPosX - _.arr[0][_.length];
                    _R.getCarActiveSlide(id);
                } else if(swapToRight){

                    var lastPosX = parseFloat(_.arr[_.arr.length - 1][_.translate]) + _.space;
                    var val = _.arr.shift();
                    _.arr.push(val);
                    if(lastSwap === val) recheck = false;
                    lastSwap = val;
                    _.arr[_.arr.length - 1].posX = _.arr[_.arr.length - 1][_.translate] = lastPosX + _.arr[_.arr.length - 2][_.length];
                    _R.getCarActiveSlide(id);
                } else recheck = false;
            }
        },

        onThrowComplete: function(id){
            var _ = _R[id].carousel;
            if (_R[id].sliderType==="carousel" && !_.fadein) {
                tpGS.gsap.to(_R[id].canvas,1,{scale:1,opacity:1});
                _.fadein=true;
            }
            _R.getCarActiveSlide(id, true);
            var focused = _.arr[_.activeSlide];
            for(var i = 0; i < _R[id].slides.length; i++){
                if(_R[id].slides[i] === focused.elem){

                    _.focused = parseFloat(i);
                    if(_.focused === _.oldfocused) break;
                    _R[id].pr_next_key = (_.focused).toString();
                    if(!_.animInList.includes((_.oldfocused).toString())) _.animInList.push((_.oldfocused).toString());
                    if(_.showLayersAllTime !== "all"){
                        while(_.animInList.length >= 1){
                            var oldFocusedIndex = _.animInList.pop();
                            if (_.focused!=oldFocusedIndex) _R.removeTheLayers(jQuery(_R[id].slides[oldFocusedIndex]),id);
                        }
                    }

                    _R.callingNewSlide(id, _R[id].slides[i].getAttribute('data-key'),true, true);
                    _R[id].c.trigger('restarttimer');
                    _R[id].c.trigger("revolution.nextslide.waiting");
                    triggerCarouselChange(id);
                    if (_.focused!=_.oldfocused) {
                        if(_.showLayersAllTime !== "all"){
                            if(!_.animInList.includes((_.focused).toString())) _.animInList.push((_.focused).toString());
                            _R.animateTheLayers({slide:_.focused, id:id, mode: "start"});
                            _R.animateTheLayers({slide:'individual', id:id, mode:(!_R[id].carousel.allLayersStarted ? "start" : "rebuild")});
                        }
                    }
                    for (var nbgi in _R[id].sbgs) {

                        if (!_R[id].sbgs.hasOwnProperty(nbgi) || _R[id].sbgs[nbgi].bgvid===undefined || _R[id].sbgs[nbgi].bgvid.length===0) continue;
                        if (""+_R[id].sbgs[nbgi].skeyindex===""+_.focused)
                            _R.playBGVideo(id,_R.gA(_R[id].pr_next_slide[0],"key"));
                        else
                            _R.stopBGVideo(id,_R[id].sbgs[nbgi].key);
                    }
                    _.oldfocused = _.focused;
                    break;
                }
            }

            _.draggable[_.deltaT] = 0;
            _R[id].c.trigger('restarttimer');
        },

        calculateSnap: function(id, target){
            var _ = _R[id].carousel;

            tpGS.gsap.killTweensOf(_.proxy, _.translate);

            var delta = _.orientation === "v" ? target - _.draggable.endY : target - _.draggable.endX;
            var overshoot = true;
            var direction;
            var traveled = _.orientation === "v" ? Math.abs(_.draggable.endY - _.draggable.startY) : Math.abs(_.draggable.endX - _.draggable.startX)

            _.focusedPreSnap = _.focused;
            if(_.snap){
                direction = _.direction = _.draggable[_.deltaT] >= 0 ? 'right' : 'left';
                var nsInfo = _R.getNextSlide(id, delta, direction, true, traveled < 300);

                delta = nsInfo.delta;
                overshoot = nsInfo.overshoot;
                _.target = nsInfo.target;

            } else _.target = target;



            if(!_.infinity && !_.snap || (!_.infinity && _.orientation === "v")){
                if(_.target <= (_[_.wraplength] - _.totalWidth)) _.target = (_[_.wraplength] - _.totalWidth);
                else if(_.target >= 0 && !_.snap) _.target = 0;
            }
            _.swiped = true;
            if(_.overshoot && overshoot) {
                tpGS.gsap.to(_, {duration: _.snap ? 0.3 : 0.5, lerpSpeed: 0.8});
                overshoot = Math.min(_.draggable[_.deltaT] === 0 ? Math.abs(delta)/20 : Math.abs(_.draggable[_.deltaT])/2, _[_.wraplength]/4) * Math.sign(delta);

                var time = Math.abs(overshoot/100);
                _.time = Math.min(Math.max(time/10, _.speed/1000 * 0.6), _.speed/1000);

                _.tween = tpGS.gsap.timeline({
                    onComplete: function(){
                        _R.snapCompleted(id);
                    }
                })
                _.tween.to(_.proxy, {
                    x: _.target + overshoot,
                    y: _.target + overshoot,
                    duration: _.time,
                    ease: 'power2.out'
                })
                .to(_.proxy, {
                    x: _.target,
                    y: _.target,
                    duration: Math.min(_.time * 2, 0.6),
                    ease: (_.easing.replace('.inOut', '.out').replace('.in', '.out'))
                }, 'overshoot')
                .to(_, {duration: Math.min(_.time * 2, 0.6), lerpSpeed: 1
                }, 'overshoot');
            } else{
                time = Math.abs(delta/100);
                _.time = Math.min(Math.max(time/10, _.speed/1000 * 0.6), _.speed/1000);

                _.tween = tpGS.gsap.to(_.proxy, {
                    x: _.target,
                    y: _.target,
                    duration: _.time,
                    ease: (_.easing.replace('.inOut', '.out').replace('.in', '.out')),
                    onComplete: function(){
                        _R.snapCompleted(id);
                    }
                });
                tpGS.gsap.to(_, {duration: _.time, lerpSpeed: 1});
            }

        },

        carLerpHandler: function(id, skip){
            var _ = _R[id].carousel;
            if(skip !== "skip") _.lerp = requestAnimationFrame(_.lerpHandler);
            var t = parseFloat(_.proxy._gsap[_.translate]);
            var f = parseFloat(_.follower._gsap[_.translate]);
            var df = f + (t-f) * _.lerpSpeed;
            var dx = df - f;

            tpGS.gsap.set(_R[id].canvas, {
                skewX: _.skewX * Math.max(-1, Math.min(1, dx/100)),
                skewY: _.skewY * Math.max(-1, Math.min(1, dx/100))
            });

            if(_.orientation === "h"){
                tpGS.gsap.set(_.follower, {x: "+=" + dx});
                tpGS.gsap.set(_.arr, {x: "+=" + dx});
            } else {
                tpGS.gsap.set(_.follower, {y: "+=" + dx});
                tpGS.gsap.set(_.arr, {y: "+=" + dx});
            }

            _R.swapCarouselSlides(id);
            _R.applyDistanceEffect(id);
        },

        snapCompleted: function(id){
            var _ = _R[id].carousel;
            _.lerp = cancelAnimationFrame(_.lerp);
            _.swiped = false;
            tpGS.gsap.set(_.follower, {
                x:  _.proxy._gsap[_.translate],
                y:  _.proxy._gsap[_.translate]
            });
            _R.onThrowComplete(id);
        },

        applyDistanceEffect: function(id){
            var _ = _R[id].carousel;
            var shortest = Infinity;
            var loaded = 0;
            var closest = 0;
            if(_.lastSlideProgress === undefined) _.lastSlideProgress = 1;
            var tempOffset = _.startOffset;
            _.startOffsetCache = _.startOffset;
            if(_.tempAlign === undefined) _.tempAlign = _.align;
            if(_.orientation === "v" && !_.infinity && !_.justify) {
                tempOffset = _.startOffset + (_[_.wraplength] - _[_.slide_length] - _.startOffset) * (1 - _.lastSlideProgress);
            }
            for(var i in _.arr){
                var d = (parseFloat(_.arr[i][_.translate]) - tempOffset);
                if(_.infinity) d %= _.totalWidth;
                if(Math.abs(d) < shortest){
                    for(var j = 0; j < _R[id].slides.length; j++){
                        if(_R[id].slides[j] ===_.arr[i].elem) {
                            closest = j;
                            _.closestArr = i;
                        }
                    }
                    shortest = Math.abs(d);
                }

                if(_.arr[i].loaded) loaded++;


                if(!_.infinity && !_.snap){
                    if(_.activeSlide === 0) {d = parseFloat(_.arr[i][_.translate]);}
                    else if(_.activeSlide === _.arr.length - 1) d = (parseFloat(_.arr[i][_.translate]) - (_[_.wraplength] - _.arr[i][_.length]));
                }

                var sign = Math.sign(d);
                var progress = _.arr[i].progress = Math.abs(d)/(_[_.slide_length] + _.space);
                if(_.orientation === "v" && !_.infinity && i == _.slideamount -1){
                    if(progress <= (_.direction === "left" ? 0.9 : 0.1) && !_.vertAlignBottom){
                        tpGS.gsap.to(_, {lastSlideProgress: 0,duration: 0.2});
                        _.vertAlignDefault = false;
                        _.vertAlignBottom = true;
                        _.tempAlign = "end";
                        updateEdgeCalculation(id, "end");
                    } else if(progress > (_.direction === "left" ? 0.9 : 0.1) && !_.vertAlignDefault){
                        tpGS.gsap.to(_, {lastSlideProgress: 1, duration: 0.2});
                        _.vertAlignDefault = true;
                        _.vertAlignBottom = false;
                        _.tempAlign = _.align;
                        updateEdgeCalculation(id, _.align);
                    }
                }


                _.arr[i].sign = sign;

                var pv = _.arr[i].progress;
                pv = pv/Math.ceil(_.pDiv) * (_.tempAlign === 'center' ? 1 : _.tempAlign === 'start' ? sign : -sign);

                var p = Math.min(_.arr[i].progress, 1)/1;
                var zIndex = 100 - 5 * Math.round(_.arr[i].progress);
                var obj = {};
                if(!_.justify && _.spin !== 'off'){

                    obj[_.translate] = tempOffset;
                    if(_.spin === '2d') {
                        obj.rotation = _.spinAngle * progress * (_.orientation === 'h' ? sign : -sign);
                        if(_.orientation === 'h') obj.transformOrigin = 'center ' + _.spinR + 'px 0';
                        else obj.transformOrigin = _.spinR + 'px center 0';
                    } else {
                        if(_.orientation === 'h'){
                            obj.rotationY = _.spinAngle * _.arr[i].progress * -sign;
                        } else {
                            obj.rotationX = _.spinAngle * _.arr[i].progress * sign;
                        }
                        obj.transformOrigin = 'center center ' + _.spinR + 'px';
                    }
                } else if(_.minScale === 0 || _.justify) obj[_.translate] = _.arr[i][_.translate];
                else {
                    var scale = 1 - (_.vary_scale ? pv : p) * (1 - _.minScale);
                    var translateOffset = _.offsetScale ? _.arr[i].sign * (_[_.slide_length] + _.space - (_[_.slide_length] + _.space) * scale)/2 * _.arr[i].progress
                            :_.arr[i].sign * (_[_.slide_length] - _[_.slide_length] * scale)/2 * _.arr[i].progress;
                    obj[_.translate] = _.arr[i][_.translate] - translateOffset;
                    if(window.isSafari11) obj.z = (1-scale) * -150;
                    obj.scale = scale;
                }

                obj.opacity = 1;
                if(!_.justify){
                    if(_.maxRotation !== 0) obj.rotationY = _.maxRotation * (_.vary_rotation ? pv : p) * -sign;
                    obj.opacity = 1 + ((_.maxOpacity - 1) * (_.vary_fade ? pv : p));
                    if(pv > _.edgeRatio) obj.opacity = _.oRange(pv);
                    else if(pv < 0) obj.opacity = _.oRangeMin(pv);
                    else if(_.maxOpacity === 1) obj.opacity = 1;
                }

                obj.zIndex = zIndex;
                if(obj.opacity > 0 ) {
                    obj.visibility = "visible";
                    tpGS.gsap.set(_.arr[i].elem, obj);
                } else {
                    tpGS.gsap.set(_.arr[i].elem, {
                        visibility: "hidden",
                        opacity: obj.opacity
                    });
                }

            }

            if(closest !== _.closest) {
                _.closest = closest;
                if(loaded !== _.arr.length) _R.loadVisibleCarouselItems(id,true, _.closest);

                if(_.draggable.isPressed){
                    _.focused = _.closest;
                    _R[id].pr_next_key = (_.focused).toString();
                    _.oldfocused = _.oldfocused===undefined ? 0 : _.oldfocused;
                    if (_R[id].carousel.allLayersStarted) _R.updateCarouselRows(id); else _R.carouselRowAdjustment(_,id,_.focused);

                    if(_.showLayersAllTime !== "all"){
                        if(!_.animInList.includes((_.oldfocused).toString())) _.animInList.push((_.oldfocused).toString());
                        while(_.animInList.length >= 1){
                            var oldFocusedIndex = _.animInList.pop();
                            if (_.focused!=oldFocusedIndex) _R.removeTheLayers(jQuery(_R[id].slides[oldFocusedIndex]),id);
                        }
                    }

                    if (_.focused!=_.oldfocused) {
                        if(_.showLayersAllTime !== "all"){
                            if(!_.animInList.includes((_.focused).toString())) _.animInList.push((_.focused).toString());
                            _R.animateTheLayers({slide:_.focused, id:id, mode: "start"});
                            _R.animateTheLayers({slide:'individual', id:id, mode:(!_R[id].carousel.allLayersStarted ? "start" : "rebuild")});
                        }
                        triggerCarouselChange(id, true);
                        _.oldfocused = _.focused;
                    }

                    _R[id].c.trigger("revolution.nextslide.waiting");
                }

                for (var nbgi in _R[id].sbgs) {

                    if (!_R[id].sbgs.hasOwnProperty(nbgi) || _R[id].sbgs[nbgi].bgvid===undefined || _R[id].sbgs[nbgi].bgvid.length===0) continue;
                    if (""+_R[id].sbgs[nbgi].skeyindex===""+_.focused) _R.playBGVideo(id,_R.gA(_R[id].pr_next_slide[0],"key"));
                    else {
                        _R.stopBGVideo(id,_R[id].sbgs[nbgi].key);
                    }
                }
            }
        },

        getCarActiveSlide: function(id){
            var _ = _R[id].carousel;
            var shortestDistance = 999999;
            var focused;
            var index = 0;

            for(var i in _.arr){
                if(!_.arr.hasOwnProperty(i)) continue;
                var dist;
                if(_R[id].carousel.align === 'center'){
                    dist = Math.abs((parseFloat(_.arr[i][_.translate]) - (_[_.wraplength] - _.arr[i][_.length])/2));
                } else if(_R[id].carousel.align === 'start'){
                    dist = Math.abs((parseFloat(_.arr[i][_.translate])));
                } else {
                    dist = Math.abs((parseFloat(_.arr[i][_.translate]) - (_[_.wraplength] - _.arr[i][_.length])));
                }

                if(_.vertAlignBottom) dist = Math.abs((parseFloat(_.arr[i][_.translate]) - (_[_.wraplength] - _.arr[i][_.length])));

                if(dist < shortestDistance) {
                    focused = _.arr[i];
                    shortestDistance = dist;
                    index = i;
                }
            }

            index = parseInt(index);

            _.activeSlide = index;
            return focused;
        },

        loadVisibleCarouselItems : function(id,forceload, closest) {
            var _ = _R[id].carousel;
            var ar =[];
            var focused = closest ? _.closest : _.focused;
            _.focused = parseInt(focused,0);
            _.focused = _R.isNumeric(focused) ? focused : 0;
            for (var i=0;i<Math.ceil(_R[id].carousel.maxVisibleItems/2);i++) {
                var n = _R[id].carousel.align ==="end" ? focused-i : focused + i,
                    b = _R[id].carousel.align ==="center" ? focused-i : _R[id].carousel.align ==="start" ? _R[id].carousel.maxVisibleItems + n - 1: n - _R[id].carousel.maxVisibleItems+1;

                n = n>=_R[id].slideamount ? 0 + (n-_R[id].slideamount) : n;
                b = b>=_R[id].slideamount ? 0 + (b-_R[id].slideamount) : b;
                n = n<0 ? _R[id].slideamount +n : n;
                b = b<0 ? _R[id].slideamount +b : b;

                ar.push(_R[id].slides[n]);
                if (n!==b) ar.push(_R[id].slides[b]);

                if(_.arr)for(var j = 0; j < _.arr.length; j++){
                    if(_R[id].slides[n] === _.arr[j].elem) {
                        _.arr[j].loaded = true;
                    }
                    if(_R[id].slides[b] === _.arr[j].elem) _.arr[j].loaded = true;
                }
            }


            if (forceload) {
                _R.loadImages(ar,id,1);
                _R.waitForCurrentImages(ar,id);
            }
            return ar;
        },

        // ORGANISE THE CAROUSEL ELEMENTS IN POSITION AND TRANSFORMS
        organiseCarousel : function(id,direction,setmaind,unli,noanim) {

            var _ = _R[id].carousel;

            // SECOND RUN FOR NEGATIVE ADJUSTMENETS
            if (_R[id].slides)
            for (var i=0;i<_R[id].slides.length;i++) {
                var	tr = { width : _.justify===true ? _.slide_widths[i] : _.slide_width};

                if(_.spin === 'off') tr.transformOrigin = "50% "+(_.orientation === "h" ? _.vertical_align : "center");
                tr.force3D = true;
                tr.transformStyle = _R[id].parallax.type!="3D" && _R[id].parallax.type!="3d" ? "flat" : "preserve-3d";

                if (noanim!==true) {
                    tpGS.gsap.set(_R[id].slides[i],tr);

                }
            };

        },
        updateCarouselRows : function(id) {
            if (_R[id].sliderType === "carousel") for ( var si=0;si<_R[id].slideamount;si++) _R.carouselRowAdjustment(_R[id].carousel,id,si);
        },
        carouselRowAdjustment : function(_,id,f) {
            if (_.slidesWithRowAdjustions!==undefined && _.slidesWithRowAdjustions[f]===undefined) {_.slidesWithRowAdjustions[f] = true;_R.getRowHeights(id,f);_R.putMiddleZoneInPosition(id,f);}
        },

        getNextSlide: function(id, delta, direction, isThrow, goNext){
            var _ = _R[id].carousel;
            var trackIndex;
            var overshoot = true;
            var tempDelta = 0;
            var sameSlide = false;
            var prevDelta = delta;
            var oDelta = delta;
            if(_.trackIndex === undefined){
                for(var i = 0; i < _.trackArr.length; i++){
                    if(_.arr[_.activeSlide].elem === _.trackArr[i].elem){
                        trackIndex = i;
                        break;
                    }
                }
            } else trackIndex = _.trackIndex;

            if(direction === undefined) sameSlide = true;
            var breakLoop = false;
            while((_.snap || !isThrow) && !sameSlide){

                if(breakLoop || (isThrow && Math.abs(tempDelta) >= Math.abs(delta)) || (delta === undefined && _R[id].slides[_.focused] === _.trackArr[trackIndex].elem)){
                    delta = tempDelta;
                    break;
                }
                prevDelta = tempDelta
                var nextStep =_R.getNext(id, direction, tempDelta, trackIndex, isThrow === undefined);
                breakLoop = nextStep.breakLoop;
                if(!breakLoop) {
                    tempDelta = nextStep.tempDelta;
                    trackIndex = nextStep.trackIndex;
                }
            }

            var target = _.lastPos + delta;
            if(isThrow && _.focusedOnPress != _.focusedPreSnap) {
                target = Math.abs((oDelta + _.lastPos) - target) <= Math.abs((oDelta + _.lastPos) - (prevDelta + _.lastPos)) ? target : prevDelta + _.lastPos;
            }
            if(sameSlide){
                target = _.lastPos;
                delta = -_.arr[_.closestArr][_.translate];
            }
            return {target: target, overshoot: overshoot, delta: delta, trackIndex: trackIndex};
        },

        getNext: function(id, direction, tempDelta, trackIndex, isNav){
            var _ = _R[id].carousel
            var sign = direction === 'right' ? 1 : -1;
            var breakLoop = false;

            if(_.align === 'start' && direction === 'left') tempDelta += (_.trackArr[trackIndex][_.length] + _.space) * sign;
            else if(_.align === 'center') tempDelta += (_.trackArr[trackIndex][_.length] + _.space)/2 * sign;
            else if(_.align === 'end' && direction === "right") tempDelta += (_.trackArr[trackIndex][_.length] + _.space) * sign;

            if(direction === 'right') {
                trackIndex--;
                if(trackIndex < 0) {
                    trackIndex = _.infinity ? _.trackArr.length - 1 : trackIndex + 1;
                    if(!_.infinity) breakLoop = true;
                }
            } else {
                trackIndex++;
                if(trackIndex >= _.trackArr.length) {
                    trackIndex = _.infinity ? 0 : trackIndex - 1;
                    if(!_.infinity) breakLoop = true;
                }
            }
            if(!breakLoop){
                if(_.align === 'start' && direction === 'right') tempDelta += (_.trackArr[trackIndex][_.length] + _.space) * sign;
                if(_.align === 'center') tempDelta += (_.trackArr[trackIndex][_.length] + _.space)/2 * sign;
                else if(_.align === 'end' && direction === "left") tempDelta += (_.trackArr[trackIndex][_.length] + _.space) * sign;
            }

            return {tempDelta: tempDelta, trackIndex: trackIndex, breakLoop: breakLoop}
        },

        getCarDir: function(id, o, n){
            var _ = _R[id].carousel;
            var d = n - o;
            var dir = 'right';
            _.sameSlide = false;
            var stepL = 0;
            var stepR = 0;
            if(_.infinity) {

                var i = o;
                while(i != n) {stepR += 1;i++; i = i >= _.slideamount ? 0 : i}
                i = o;
                while(i != n) {stepL += 1;i--; i = i < 0 ? _.slideamount - 1 : i}
                dir = stepR <= stepL ? "left" : "right";
            } else if(!_.infinity) {
                dir = n - o >= 0 ? 'left' : 'right';
                stepR = Math.abs(n - o);
                if(d === 0) {
                    dir = "right";
                    _.sameSlide = true;
                }
            }
            _.steps = stepR <= stepL ? stepR : stepL;
            _.direction = dir;
            return dir;
        },

        getLastPos: function(id){
            var _ = _R[id].carousel;

            if(!_.trackArr) return;

            var posOffset = 0;
            var offset = 0;

            for(var i = 0; i < _.trackArr.length; i++){
                if(_R[id].slides[_.closest] === _.trackArr[i].elem) _.trackIndex = i;
                if(_R[id].slides[_.closest] === _.arr[i].elem) {
                    posOffset = _.arr[i][_.translate];

                    if(_.align === 'center') offset = (_[_.wraplength] - _.arr[i][_.length])/2;
                    if(_.align === 'end') offset = (_[_.wraplength] - _.arr[i][_.length]);
                    _.lastOffset = offset;
                }
            }
            _.lastPos = parseFloat(_.proxy._gsap[_.translate]) - posOffset - (parseFloat(_.proxy._gsap[_.translate]) - parseFloat(_.follower._gsap[_.translate]));
            _.lastPos += offset;
        },

        swipeAnimate : function(obj) {
            var _ = _R[obj.id].carousel;
            var id = obj.id;
            _R.getLastPos(id);

            if(!_.arr || !_.arr[_.closestArr]) return;
            if(_.arr[_.closestArr].elem == _R[obj.id].slides[_.focused]) return;


            var direction = _R.getCarDir(id, _.trackIndex, _.focused);
            var ns =_R.getNextSlide(obj.id, undefined, direction, false);
            _.target = ns.target;

            if(!_.infinity && !_.snap || (!_.infinity && _.orientation === "v")){
                if(_.target <= (_[_.wraplength] - _.totalWidth)) _.target = (_[_.wraplength] - _.totalWidth);
                else if(_.target >= 0 && !_.snap) _.target = 0;
            }

            _.lerpSpeed = 1;
            if(_.tween && _.tween.kill) {_.tween.kill(); delete _.tween;}
            _.tween = tpGS.gsap.to(_.proxy, {
                x: _.target,
                y: _.target,
                ease: _.easing,
                duration: _.speed/1000 + (_.steps >= 2 ? (_.steps - 1) * _.speed/2000 : 0),
                onComplete: function(){
                    _.lerp = cancelAnimationFrame(_.lerp);
                    _R.carLerpHandler(id, "skip");
                    _.activeSlide = _.closestArr;
                    _R.snapCompleted(id);
                }
            });
            _.lerp = cancelAnimationFrame(_.lerp)
            if(!_.lerp)_.lerp = requestAnimationFrame(_.lerpHandler);

        },
        defineCarouselElements : function(id) {
            var _ = _R[id].carousel;
            _.infbackup = _.infinity;
            _.maxVisiblebackup = _.maxVisibleItems;

            // SET DEFAULT OFFSETS TO 0
            _.slide_offset = "none";
            _.slide_offset = 0;
            _.cached_slide_offset = 0;

            // SET UL REFERENCE
            _.wrap = jQuery(_R[id].canvas[0].parentNode);


            // CHANGE PERSPECTIVE IF PARALLAX 3D SET
            if (_.maxRotation!==0) if (_R[id].parallax.type==="3D" || _R[id].parallax.type==="3d") tpGS.gsap.set(_.wrap,{perspective:"1600px",transformStyle:"preserve-3d"});
        },
        setCarouselDefaults : function(id,quickmode, skip) {

            var _=_R[id].carousel;

            _.stretchCache = _.stretchCache===undefined ? _.stretch : _.stretchCache;
            _.stretch = _R[id].infullscreenmode ? true : _.stretchCache;

            // DEFAULT LI WIDTH SHOULD HAVE THE SAME WIDTH OF TH id WIDTH
            _.slide_width = Math.round(_.stretch!==true && _.orientation!=='v' ? _R[id].gridwidth[_R[id].level]*(_R[id].CM.w===0 ? 1 : _R[id].CM.w) : _R[id].canv.width);
            _.slide_height = Math.round(_.stretch!==true ? _R[id].infullscreenmode ? _R.getWinH(id) - _R.getFullscreenOffsets(id) : _R[id].gridheight[_R[id].level]*(_R[id].CM.w===0 ? 1 : _R[id].CM.w) : _R[id].canv.height);

            // TODO Krisztian, we will most likely need to move this logic somewhere else and check of any side effects
            /*if(_.orientation === "v"){
                _R[id].module.height = _.slide_height;
                _R[id].canv.height = _.slide_height;

                if(_.align !== "start") tpGS.gsap.set(_R[id].canvas, {top: (_R[id].height - _R[id].canv.height)/(_.align === "center" ? 2 : 1)});
            }*/

            _.ratio = _.slide_width / _.slide_height;

            // CALCULATE CAROUSEL WIDTH
            _.len = _R[id].slides.length;
            _.maxwidth = _R[id].slideamount*_.slide_width;
            _.maxheight = _R[id].slideamount*_.slide_height;
            if (_.justify!=true && _.maxVisiblebackup>_.len) _.maxVisibleItems = (_.len%2) ? _.len : _.len+1;


            // SET MAXIMUM CAROUSEL WARPPER WIDTH (SHOULD BE AN ODD NUMBER)
            _.wrapwidth = (_.maxVisibleItems * _.slide_width) + ((_.maxVisibleItems - 1) * _.space);
            _.wrapheight = (_.maxVisibleItems * _.slide_height) + ((_.maxVisibleItems - 1) * _.space);
            _.wrapwidth = _R[id].sliderLayout!="auto" ? _.wrapwidth>_R[id].canv.width ? _R[id].canv.width : _.wrapwidth : _.wrapwidth>_R[id].module.width ? (_R[id].module.width !== 0 ? _R[id].module.width : _R[id].canv.width) : _.wrapwidth;
            _.wrapheight = _R[id].sliderLayout!="auto" ? _.wrapheight>_R[id].canv.height ? _R[id].canv.height : _.wrapheight : _.wrapheight>_R[id].module.height ? (_R[id].module.height !== 0 ? _R[id].module.height : _R[id].canv.height) : _.wrapheight;

            if (_.justify===true) {
                _.slide_height = Math.round(_R[id].sliderLayout==="fullscreen" ? _R[id].module.height : _R[id].gridheight[_R[id].level]);
                _.slide_widths = [];
                _.slide_heights = [];
                _.slide_widthsCache = 	_.slide_widthsCache===undefined ? [] :_.slide_widthsCache;
                _.slide_heightsCache = 	_.slide_heightsCache===undefined ? [] :_.slide_heightsCache;
                _.maxwidth = 0;
                for (var i=0;i<_.len;i++) {
                    if (!_R[id].slides.hasOwnProperty(i)) continue;
                    var ir  = _R.gA(_R[id].slides[i],'iratio');
                    ir = ir===undefined || ir===0 || ir===null ? _.ratio : ir;
                    ir = parseFloat(ir);
                        _.slide_widths[i] = Math.round(_.slide_height * ir);
                        _.slide_heights[i] = Math.round(_.slide_height);
                    if (_.justifyMaxWidth!==false) _.slide_widths[i] = Math.min(_.wrapwidth,_.slide_widths[i]);
                    if (_.justifyMaxWidth!==false) _.slide_heights[i] = Math.min(_.wrapheight,_.slide_heights[i]);
                    if (_.slide_widths[i]!==_.slide_widthsCache[i]) {
                        _.slide_widthsCache[i] = _.slide_widths[i];
                            if (quickmode!==true) tpGS.gsap.set(_R[id].slides[i],{width:_.slide_widths[i]}); // KRIKI TO DO!!
                    }
                    if (_.slide_heights[i]!==_.slide_heightsCache[i]) {
                        _.slide_heightsCache[i] = _.slide_heights[i];
                            if (quickmode!==true) tpGS.gsap.set(_R[id].slides[i],{height:_.slide_heights[i]}); // KRIKI TO DO!!
                    }
                    _.maxwidth += _.slide_widths[i] +_.space;
                    _.maxheight += _.slide_heights[i] +_.space;
                }
            }


            // INFINITY MODIFICATIONS
            _.infinity = _.wrapwidth >=_.maxwidth ? false : _.infbackup;
            if(_.forceBAlign && _.slide_height < _.wrapheight * 0.6 && _.wrapwidth <_.maxwidth) _.infinity = true;
            else if(_.forceBAlign) _.infinity = false;

            if (_.quickmode!==true) {
                // SET POSITION OF WRAP CONTAINER
                _.wrapoffset = _.horizontal_align==="center" ? (_R[id].canv.width-_R[id].outNavDims.right - _R[id].outNavDims.left - _.wrapwidth)/2 : 0;
                _.wrapoffset = _R[id].sliderLayout!="auto" && _R[id].outernav ? 0 : _.wrapoffset < _R[id].outNavDims.left ? _R[id].outNavDims.left : _.wrapoffset;

                var ovf = ((_R[id].parallax.type=="3D" || _R[id].parallax.type=="3d")) ? "visible" : "hidden",
                    obj = _.horizontal_align==="right" ? {left:"auto",right:_.wrapoffset+"px", width:_.wrapwidth, overflow:ovf} : _.horizontal_align==="left"  || _.wrapwidth<_R.winW? {right:"auto",left:_.wrapoffset+"px", width:_.wrapwidth, overflow:ovf} : {right:"auto",left:"auto", width:"100%", overflow:ovf}
                //Fixed an issue where fullwidth sliders did no go fullwdith if it is not set to 100% width.
                if (_.cacheWrapObj===undefined || obj.left!==_.cacheWrapObj.left || obj.right!==_.cacheWrapObj.right || obj.width!==_.cacheWrapObj.width) {
                    window.requestAnimationFrame(function() {
                        tpGS.gsap.set(_.wrap,obj);
                        // reset left property on canvas because when carousel is created,
                        // _R[id].navOutterOffsets.left value is still incorrect
                        if(_R[id].carousel.wrapoffset > 0) tpGS.gsap.set(_R[id].canvas, {left: 0});
                    });
                    _.cacheWrapObj = jQuery.extend(true,{},obj);
                }

                // INNER OFFSET FOR RTL
                _.inneroffset = _.horizontal_align==="right" ? _.wrapwidth - _.slide_width : 0;

                // THE SCREEN WIDTH/2
                _.windhalf = _R[id].sliderLayout==="auto" ? _R[id].module.width/2 : _R.winW/2;
            }
            if(!(_.lastWrapwidth === _.wrapwidth && _.lastWrapheight === _.wrapheight))window.requestAnimationFrame(function() {
                _R.positionCarousel(id);
            });
        }
    });

    /**************************************************
        -	CAROUSEL FUNCTIONS   -
    ***************************************************/

    var getOffset = function(id,ix) {
        var _ = _R[id].carousel;
        return _.horizontal_align==="center" ?  (_.windhalf - _.slide_widths[ix]/2) - _.slidepositions[ix] :  _.horizontal_align==="left" ? 0-_.slidepositions[ix] : (_.wrapwidth-_.slide_widths[ix]) - _.slidepositions[ix];
    },

    circ = function(p) {return p<1 ? Math.sqrt(1 - (p = p - 1) * p) : Math.sqrt(p);},

    triggerCarouselChange = function(id, useNextKey) {
        var _ = _R[id].carousel
        var nextSlide = useNextKey && _R[id].slides[_R[id].pr_next_key] ? jQuery(_R[id].slides[_R[id].pr_next_key]) : _R[id].pr_next_slide;
        _R[id].c.trigger('revolution.slide.carouselchange', {
            slider:id,
            slideIndex : parseInt(_R[id].pr_active_key,0)+1,
            slideLIIndex : _R[id].pr_active_key,
            slide : nextSlide,
            currentslide : nextSlide,
            prevSlideIndex : _R[id].pr_lastshown_key!==undefined ? parseInt(_R[id].pr_lastshown_key,0)+1 : false,
            prevSlideLIIndex : _R[id].pr_lastshown_key!==undefined ? parseInt(_R[id].pr_lastshown_key,0) : false,
            prevSlide : _.oldfocused!==undefined ? _R[id].slides[_.oldfocused] : false
        });
    },

    updateEdgeCalculation = function(id, align){
        var _ = _R[id].carousel;
        if(id === undefined || _ === undefined) return
        _.pDiv = align === 'center' ? _.maxVisibleItems/2 : _.maxVisibleItems;
        _.edgeRatio = Math.floor(_.pDiv - (align === 'center' ? 0 : 1))/Math.ceil(_.pDiv);
        if(_.maxVisibleItems === 1) _.edgeRatio = 1;
        _.oEdge = _.maxOpacity === 1 ? 1 : _.vary_fade ? 1 + (_.maxOpacity - 1) * _.edgeRatio : _.maxOpacity;
        _.oEdge = _.maxVisibleItems === 1 ? _.maxOpacity : _.oEdge;
        _.oRange = _.maxVisibleItems > 1 ? tpGS.gsap.utils.mapRange(_.edgeRatio, 1, _.oEdge,  0) : tpGS.gsap.utils.mapRange(1, 1.1, _.oEdge,  0);
        _.oRangeMin = tpGS.gsap.utils.mapRange(-1/_.maxVisibleItems, -1.1/_.maxVisibleItems, 1,  0);
    },

    initCarArrays = function(id){
        var _ = _R[id].carousel;
        if(id === undefined || _ === undefined) return

        tpGS.gsap.set([_.proxy, _.follower], {x: '+=0', y: '+=0'});
        _.arr = [];
        _.trackArr = [];

        for(var i = 0; i < _R[id].slides.length; i++){
            _.arr.push({elem:_R[id].slides[i]});
            _.trackArr.push({elem:_R[id].slides[i]});
        }
    },

    // DIRECTION CHECK
    dircheck = function(d,b) {return d===null || jQuery.isEmptyObject(d) ? b : d === undefined ?  "right" : d;},
    breduc = function(a,m) {return Math.abs(a)>Math.abs(m) ? a>0 ? a - Math.abs(Math.floor(a/(m))*(m)) : a + Math.abs(Math.floor(a/(m))*(m)) : a;},

    // CAROUSEL INFINITY MODE, DOWN OR UP ANIMATION
    getBestDirection = function(a,b,max) {
        var dira = b-a,
            dirb = (b-max) - a;
        dira = breduc(dira,max);
        dirb = breduc(dirb,max);
        return Math.abs(dira)>Math.abs(dirb) ? dirb : dira;
    }

    //Support Defer and Async and Footer Loads
    window.RS_MODULES = window.RS_MODULES || {};
    window.RS_MODULES.carousel = {loaded:true, version:version};
    if (window.RS_MODULES.checkMinimal) window.RS_MODULES.checkMinimal();

    })(jQuery);