define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    let countdownInstances = [];
    let startTime = 0;
    let initCountdown = false;
    let storedCountdown = 0;
    let now = new Date().getTime();
    const second = 1000,
        minute = 60000,
        hour = 3600000,
        day = 86400000;

    const updateCountdownInstances = () => {
        countdownInstances.forEach(instance => {
            instance._tick();
        });
    }
    // Global countdown timer function
    function startCountdownTimer() {
        setInterval(() => {
            now += second;
            updateCountdownInstances();
        }, second);
    }

    function initCountdownTimer(){
        if(!initCountdown){
            startCountdownTimer();
            initCountdown = true;
        }
        // trigger counter update each time a new instace is pushed
        // remove this to init all countdowns in bulck on next interval tick
        updateCountdownInstances();
    }
    
    // All timers use the same start time value based on the first customer visit to a website 
    // This is because widgets can be added and removed anywhere without any registration and so garbage collection is not posible.
    function initLocalStorage(resetDate, resetPeriod){
        if(storedCountdown){
            return;
        }

        const storageKey = 'OXcountdown';
        storedCountdown = localStorage.getItem(storageKey);

        if (storedCountdown) {
            startTime = parseInt(storedCountdown, 10);
            // reset saved timestamp by provided reset date or expiartion time (days)
            if(resetDate && (startTime < resetDate*second) ||(resetPeriod && (now - startTime > day*resetPeriod))){
                addtoStorage();
            }
        }else{
            // save time of a customer first visit on site
            addtoStorage();
        }
        function addtoStorage(){
            startTime = now;
            localStorage.setItem(storageKey, now);
        }
    }

    $.widget('mage.OXcountdown', {
        options: {
            relative: 0,
            endDate: 0,
            endTime: 0,
            timeZone: 0,
            timeZoneDiff: true,
            daysToHours: false,
            hideEl: false,
            bodyClass: '',
            hideClass: 'expired',
            loop: false,
            save: true,
            resetDate: false, // set in seconds
            resetPeriod: 180 //days
        },

        _create: function () {
            this.inited = false;
            if (!this.options.endDate || !this.options.endTime || !this.element) {
                return;
            }

            this._initializeCountdown();

            // Add this instance to the global array
            countdownInstances.push(this);
            // load elapsed time from localstorage if save option is enabled for relative timers.
            if(this.options.relative && this.options.save){
                initLocalStorage(this.options.resetDate, this.options.resetPeriod);
            }
            initCountdownTimer();
        },

        _getVisitorTimeZoneOffset: function() {
            return ((new Date().getTimezoneOffset()) / 60);
        },

        _initializeCountdown: function () {
            this.el_days = this.element.find(".days .num");
            this.el_hours = this.element.find(".hours .num");
            this.el_minutes = this.element.find(".minutes .num");
            this.el_seconds = this.element.find(".seconds .num");

            if(this.options.daysToHours && this.el_days.length){
                this.el_days.parent().hide();
            }

            this.timeOffset = 0;
            if(this.options.timeZoneDiff){
                this.timeOffset = (this.options.timeZone - this._getVisitorTimeZoneOffset()) * hour;
            }

            this.countDown = 0; 
            this.x = 1; // multiplier for looped times
            if(this.options.relative){
                this.countDown = day * this.options.endDate + this.options.endTime * hour;
            } else{
                this.countDown = new Date(`${this.options.endDate} ${this.options.endTime}`).getTime();
            }
        },
        
        _formatNum: function(number) {
            return String(Math.floor(number)).padStart(2, '0');
        },

        _tick: function () {
            let distance = 0;
            let elapsedTime = 0;

            if(this.options.relative){
                elapsedTime = now - startTime;
                distance = (this.countDown*this.x) - elapsedTime;
            } else{
                distance = this.countDown - (now - this.timeOffset);
            }

            // is expired
            if (distance < 0) {
                if(!this.options.loop){
                    this.element.addClass(this.options.hideClass);
                    if($(this.options.hideEl).length){
                        $(this.options.hideEl).addClass(this.options.hideClass);                       
                    }
                    if(this.options.bodyClass){
                        $('body').addClass(this.options.bodyClass);                       
                    }
                } else {
                    // Reset elapsed time for looped items. Calc multiplier to keep timer running after it was expired
                    if(elapsedTime > this.countDown*this.x){
                        this.x += Math.floor(elapsedTime/this.countDown);
                    }
                }
            } else{
                if(this.options.daysToHours){
                    this.el_hours[0].textContent = this._formatNum((distance / hour) % day);
                } else{
                    this.el_days[0].textContent =this._formatNum(distance / day);
                    this.el_hours[0].textContent = this._formatNum((distance % day) / hour);
                }
                this.el_minutes[0].textContent = this._formatNum((distance % hour) / minute);
                this.el_seconds[0].textContent = this._formatNum((distance % minute) / second);
                if (!this.inited) {
                    this.element.addClass('inited');
                    this.inited = true;
                }
            }
        },
        _destroy: function() {
            const index = countdownInstances.indexOf(this);
            if (index > -1) {
                countdownInstances.splice(index, 1);
            }
            this._super();
        }
    });
});
