jQuery(function(){
  jQuery('.togbox').remove()
  jQuery('.wrap form>fieldset>fieldset').css('border', 0).hide()
  jQuery('.wrap fieldset').eq(0).show()
  jQuery('.wrap fieldset h3').eq(0).append/*after*/('<select name="repeat" class="the_repeat"></select>')
  jQuery('.wrap input[name=repeat]').each (function(e){
    var elm = jQuery('.wrap input[name="repeat"]').eq(e)
    var e = elm.parents('legend')
    jQuery('select[name="repeat"]').append('<option value="'+elm.val()+'">'+e.text()+'</option>')
  }).attr('disabled', 'disabled').parent().parent().hide()
  jQuery('select[name="repeat"]').change(changeRepeat)
  // jQuery('#range_until').click(function(){jQuery('#range_ends_until').trigger('focus')})
  jQuery(":text[name$=_date],:text[name$=_until]").date_input();
  jQuery(":text[name$=_time]").time_input();
  jQuery('#events_all_day').click(function(){
    if (!jQuery(this).attr('checked'))
      jQuery('[name$=_time]').show()
    else
      jQuery('[name$=_time]').hide()
  })

  jQuery(':text[name$=_date]').eq(0).change(function(){
    var t=jQuery(':text[name$=_date]').eq(1)
    if (jQuery.trim(t.val()) == '')
      t.val(jQuery(this).val()).change()
  })

  /**
   * Reseting the getting by request
   */
  if (jQuery('#events_all_day').attr('checked'))
    jQuery('#events_when_start_time, #events_when_end_time').hide()
  jQuery('select[name=repeat]').val(jQuery('input[name=repeat]:checked').val())
  changeRepeat()
})

changeRepeat = function(){
  thi=jQuery('select[name="repeat"]')
  jQuery('input[name="repeat"]').parents('fieldset').hide()
  jQuery('input[name="repeat"][value="'+jQuery(thi).val()+'"]').parents('fieldset').show()
  jQuery('.the_no_repeat').hide()
  if (jQuery(thi).val()!='no_repeat') jQuery('.wrap .the_range').eq(0).show()
  else jQuery('.wrap .the_range').eq(0).hide()
}
/**
 * Time Input
 */

TimeInput = (function(jQuery){
  function TimeInput (elm) {
    this.elm = jQuery(elm)
    this.build();
  };
  TimeInput.prototype = {
    build:function(){
      this.elm.bind('click', {thi:this}, this.focus)
      this.elm.attr('__TimeInput', '1')
    },
    focus:function(e){
      var thi = e.data.thi
      jQuery('[__TimeInput]')
        .unbind('click', thi.focus)
        .bind('click', {thi:thi}, thi.focus)
      jQuery('.__TimeInput').remove()
      thi.elm=jQuery(e.target)
      itens = []
      var div = jQuery('<div />')
      div.css({
          position:'absolute',
          zIndex:9,
          background:'white',
          width: jQuery(this).outerWidth(),
          height: '12em',
          overflow: 'auto',
          left: jQuery(this).offset().left,
          top: jQuery(this).position().top + jQuery(this).outerHeight({margin: true})
        })
        .addClass('__TimeInput')
      for (var i = 0; i<24;i++)
        div
          .append(jQuery('<a href="#">'+(i<10?0:'')+i+':00</a>'))
          .append(jQuery('<a href="#">'+(i<10?0:'')+i+':30</a>'))
      div.children()
        .css({
          display:'block',
          textDecoration:'none',
          color:'#003C78',
          padding: 3,
          outline: 'none'
        })
        .hover(
          function(){jQuery(this).css({background:'#003C78', color:'#FFFFFF'})},
          function(){jQuery(this).css({background:'#FFFFFF',color:'#003C78'})}
        )
        .bind('click', {thi: thi}, thi.selectTime)
      if (jQuery.browser.msie && jQuery.browser.version < 7) {
        var ieframe = jQuery('<iframe frameborder="0" src="#"></iframe>')
        ieframe
        .addClass('__TimeInput')
        .css({
          position:'absolute',
          zIndex:8,
          background:'white',
          width: jQuery(this).outerWidth(),
          height: '12em',
          overflow: 'auto',
          left: jQuery(this).offset().left,
          top: jQuery(this).position().top + jQuery(this).outerHeight({margin: true})
        })
        jQuery(this).after(ieframe).after(div)
      } else {
        jQuery(this).after(div)
      }
      jQuery(thi.elm).unbind('click', thi.focus)
      jQuery([window, document.body]).bind('click', {thi:thi}, thi.terminate)
    },
    selectTime:function(e) {
      var thi = e.data.thi
      var elm=jQuery(this)
      elm.parent()
        .prev().val(elm.text())
        .bind('click', {thi: thi}, thi.focus)
      jQuery('.__TimeInput').remove()
      return false;
    },
    terminate:function(e){
      var thi = e.data.thi
      if (e.target != thi.elm[0]) {
        jQuery('.__TimeInput').remove()
        thi.elm.bind('click', {thi: thi}, thi.focus)
        jQuery([window, document.body]).unbind('click', thi.terminate)
      }
    }
  }
  jQuery.fn.time_input = function() {
    return this.each(function() { new TimeInput(this); });
  };
  return TimeInput;
})(jQuery);

DateInput = (function($) { // Localise the $ function

function DateInput(el, opts) {
  if (typeof(opts) != "object") opts = {};
  $.extend(this, DateInput.DEFAULT_OPTS, opts);

  this.input = $(el);
  this.bindMethodsToObj("show", "hide", "hideIfClickOutside", "selectDate", "prevMonth", "nextMonth");

  this.build();
  this.selectDate();
  this.hide();
};
DateInput.DEFAULT_OPTS = {
  month_names: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
  short_month_names: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
  short_day_names: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
  start_of_week: 1
};
DateInput.prototype = {
  build: function() {
    this.monthNameSpan = $('<span class="month_name"></span>');
    var monthNav = $('<p class="month_nav"></p>').append(
      $('<a href="#" class="prev">&laquo;</a>').click(this.prevMonth),
      " ", this.monthNameSpan, " ",
      $('<a href="#" class="next">&raquo;</a>').click(this.nextMonth)
    );

    var tableShell = "<table><thead><tr>";
    $(this.adjustDays(this.short_day_names)).each(function() {
      tableShell += "<th>" + this + "</th>";
    });
    tableShell += "</tr></thead><tbody></tbody></table>";

    this.dateSelector = this.rootLayers = $('<div class="date_selector"></div>').append(monthNav, tableShell).appendTo(document.body);

    if ($.browser.msie && $.browser.version < 7) {
      this.ieframe = $('<iframe class="date_selector_ieframe" frameborder="0" src="#"></iframe>').insertBefore(this.dateSelector);
      this.rootLayers = this.rootLayers.add(this.ieframe);
    };

    this.tbody = $("tbody", this.dateSelector);

    // The anon function ensures the event is discarded
    this.input.change(this.bindToObj(function() { this.selectDate(); }));
  },

  selectMonth: function(date) {
    this.currentMonth = new Date(date.getFullYear(), date.getMonth(), 1);

    var rangeStart = this.rangeStart(date), rangeEnd = this.rangeEnd(date);
    var numDays = this.daysBetween(rangeStart, rangeEnd);
    var dayCells = "";

    for (var i = 0; i <= numDays; i++) {
      var currentDay = new Date(rangeStart.getFullYear(), rangeStart.getMonth(), rangeStart.getDate() + i);

      if (this.isFirstDayOfWeek(currentDay)) dayCells += "<tr>";

      if (currentDay.getMonth() == date.getMonth()) {
        dayCells += '<td date="' + this.dateToString(currentDay) + '"><a href="#">' + currentDay.getDate() + '</a></td>';
      } else {
        dayCells += '<td class="unselected_month" date="' + this.dateToString(currentDay) + '">' + currentDay.getDate() + '</td>';
      };

      if (this.isLastDayOfWeek(currentDay)) dayCells += "</tr>";
    };

    this.monthNameSpan.empty().append(this.monthName(date) + " " + date.getFullYear());
    this.tbody.empty().append(dayCells);

    $("a", this.tbody).click(this.bindToObj(function(event) {
      this.selectDate(this.stringToDate($(event.target).parent().attr("date")));
      this.hide();
      return false;
    }));

    $("td[date=" + this.dateToString(new Date()) + "]", this.tbody).addClass("today");
  },

  selectDate: function(date) {
    if (typeof(date) == "undefined") {
      date = this.stringToDate(this.input.val());
    };

    if (date) {
      this.selectedDate = date;
      this.selectMonth(date);
      var stringDate = this.dateToString(date);
      $('td[date=' + stringDate + ']', this.tbody).addClass("selected");

      if (this.input.val() != stringDate) {
        this.input.val(stringDate).change();
      };
    } else {
      this.selectMonth(new Date());
    };
  },

  show: function() {
    this.rootLayers.css("display", "block");
    this.setPosition();
    this.input.unbind("focus", this.show);
    $([window, document.body]).click(this.hideIfClickOutside);
  },

  hide: function() {
    this.rootLayers.css("display", "none");
    $([window, document.body]).unbind("click", this.hideIfClickOutside);
    this.input.focus(this.show);
  },

  hideIfClickOutside: function(event) {
    if (event.target != this.input[0] && !this.insideSelector(event)) {
      this.hide();
    };
  },

  stringToDate: function(string) {
    var matches;
    if (matches = string.match(/^(\d{1,2}) ([^\s]+) (\d{4,4})$/)) {
      return new Date(matches[3], this.shortMonthNum(matches[2]), matches[1]);
    } else {
      return null;
    };
  },

  dateToString: function(date) {
    return date.getDate() + " " + this.short_month_names[date.getMonth()] + " " + date.getFullYear();
  },

  setPosition: function() {
    var offset = this.input.offset();
    this.rootLayers.css({
      top: offset.top + this.input.outerHeight(),
      left: offset.left
    });

    if (this.ieframe) {
      this.ieframe.css({
        width: this.dateSelector.outerWidth(),
        height: this.dateSelector.outerHeight()
      });
    };
  },

  moveMonthBy: function(amount) {
    this.selectMonth(new Date(this.currentMonth.setMonth(this.currentMonth.getMonth() + amount)));
  },

  prevMonth: function() {
    this.moveMonthBy(-1);
    return false;
  },

  nextMonth: function() {
    this.moveMonthBy(1);
    return false;
  },

  monthName: function(date) {
    return this.month_names[date.getMonth()];
  },

  insideSelector: function(event) {
    var offset = this.dateSelector.offset();
    offset.right = offset.left + this.dateSelector.outerWidth();
    offset.bottom = offset.top + this.dateSelector.outerHeight();

    return event.pageY < offset.bottom &&
           event.pageY > offset.top &&
           event.pageX < offset.right &&
           event.pageX > offset.left;
  },

  bindToObj: function(fn) {
    var self = this;
    return function() { return fn.apply(self, arguments) };
  },

  bindMethodsToObj: function() {
    for (var i = 0; i < arguments.length; i++) {
      this[arguments[i]] = this.bindToObj(this[arguments[i]]);
    };
  },

  indexFor: function(array, value) {
    for (var i = 0; i < array.length; i++) {
      if (value == array[i]) return i;
    };
  },

  monthNum: function(month_name) {
    return this.indexFor(this.month_names, month_name);
  },

  shortMonthNum: function(month_name) {
    return this.indexFor(this.short_month_names, month_name);
  },

  shortDayNum: function(day_name) {
    return this.indexFor(this.short_day_names, day_name);
  },

  daysBetween: function(start, end) {
    start = Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
    end = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate());
    return (end - start) / 86400000;
  },

  changeDayTo: function(to, date, direction) {
    var difference = direction * (Math.abs(date.getDay() - to - (direction * 7)) % 7);
    return new Date(date.getFullYear(), date.getMonth(), date.getDate() + difference);
  },

  rangeStart: function(date) {
    return this.changeDayTo(this.start_of_week, new Date(date.getFullYear(), date.getMonth()), -1);
  },

  rangeEnd: function(date) {
    return this.changeDayTo((this.start_of_week - 1) % 7, new Date(date.getFullYear(), date.getMonth() + 1, 0), 1);
  },

  isFirstDayOfWeek: function(date) {
    return date.getDay() == this.start_of_week;
  },

  isLastDayOfWeek: function(date) {
    return date.getDay() == (this.start_of_week - 1) % 7;
  },

  adjustDays: function(days) {
    var newDays = [];
    for (var i = 0; i < days.length; i++) {
      newDays[i] = days[(i + this.start_of_week) % 7];
    };
    return newDays;
  }
};

$.fn.date_input = function(opts) {
  return this.each(function() { new DateInput(this, opts); });
};
$.date_input = { initialize: function(opts) {
  $("input.date_input").date_input(opts);
} };

return DateInput;
})(jQuery); // End localisation of the $ function

/**
 * Customizando o DateInput
 */
jQuery.extend(DateInput.DEFAULT_OPTS, {
  stringToDate: function(string) {
    var matches;
    if (matches = string.match(/^(\d{4,4})-(\d{2,2})-(\d{2,2})$/)) {
      return new Date(matches[1], matches[2] - 1, matches[3]);
    } else {
      return null;
    };
  },

  dateToString: function(date) {
    var month = (date.getMonth() + 1).toString();
    var dom = date.getDate().toString();
    if (month.length == 1) month = "0" + month;
    if (dom.length == 1) dom = "0" + dom;
    return date.getFullYear() + "-" + month + "-" + dom;
  }
});

/* Copyright (c) 2007 Paul Bakaus (paul.bakaus@googlemail.com) and Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * $LastChangedDate$
 * $Rev$
 *
 * Version: @VERSION
 *
 * Requires: jQuery 1.2+
 */

(function($){

$.dimensions = {
  version: '@VERSION'
};

// Create innerHeight, innerWidth, outerHeight and outerWidth methods
$.each( [ 'Height', 'Width' ], function(i, name){

  // innerHeight and innerWidth
  $.fn[ 'inner' + name ] = function() {
    if (!this[0]) return;

    var torl = name == 'Height' ? 'Top'    : 'Left',  // top or left
        borr = name == 'Height' ? 'Bottom' : 'Right'; // bottom or right

    return this.css('display') != 'none' ? this[0]['client' + name] : num( this, name.toLowerCase() ) + num(this, 'padding' + torl) + num(this, 'padding' + borr);
  };

  // outerHeight and outerWidth
  $.fn[ 'outer' + name ] = function(options) {
    if (!this[0]) return;

    var torl = name == 'Height' ? 'Top'    : 'Left',  // top or left
        borr = name == 'Height' ? 'Bottom' : 'Right'; // bottom or right

    options = $.extend({ margin: false }, options || {});

    var val = this.css('display') != 'none' ?
        this[0]['offset' + name] :
        num( this, name.toLowerCase() )
          + num(this, 'border' + torl + 'Width') + num(this, 'border' + borr + 'Width')
          + num(this, 'padding' + torl) + num(this, 'padding' + borr);

    return val + (options.margin ? (num(this, 'margin' + torl) + num(this, 'margin' + borr)) : 0);
  };
});

// Create scrollLeft and scrollTop methods
$.each( ['Left', 'Top'], function(i, name) {
  $.fn[ 'scroll' + name ] = function(val) {
    if (!this[0]) return;

    return val != undefined ?

      // Set the scroll offset
      this.each(function() {
        this == window || this == document ?
          window.scrollTo(
            name == 'Left' ? val : $(window)[ 'scrollLeft' ](),
            name == 'Top'  ? val : $(window)[ 'scrollTop'  ]()
          ) :
          this[ 'scroll' + name ] = val;
      }) :

      // Return the scroll offset
      this[0] == window || this[0] == document ?
        self[ (name == 'Left' ? 'pageXOffset' : 'pageYOffset') ] ||
          $.boxModel && document.documentElement[ 'scroll' + name ] ||
          document.body[ 'scroll' + name ] :
        this[0][ 'scroll' + name ];
  };
});

$.fn.extend({
  position: function() {
    var left = 0, top = 0, elem = this[0], offset, parentOffset, offsetParent, results;

    if (elem) {
      // Get *real* offsetParent
      offsetParent = this.offsetParent();

      // Get correct offsets
      offset       = this.offset();
      parentOffset = offsetParent.offset();

      // Subtract element margins
      offset.top  -= num(elem, 'marginTop');
      offset.left -= num(elem, 'marginLeft');

      // Add offsetParent borders
      parentOffset.top  += num(offsetParent, 'borderTopWidth');
      parentOffset.left += num(offsetParent, 'borderLeftWidth');

      // Subtract the two offsets
      results = {
        top:  offset.top  - parentOffset.top,
        left: offset.left - parentOffset.left
      };
    }

    return results;
  },

  offsetParent: function() {
    var offsetParent = this[0].offsetParent;
    while ( offsetParent && (!/^body|html$/i.test(offsetParent.tagName) && $.css(offsetParent, 'position') == 'static') )
      offsetParent = offsetParent.offsetParent;
    return $(offsetParent);
  }
});

function num(el, prop) {
  return parseInt($.curCSS(el.jquery?el[0]:el,prop,true))||0;
};

})(jQuery);