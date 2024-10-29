=== Agenda ===
Contributors: dgmike
Donate link: http://www.dgmike.com.br
Tags: events,post,manipulate,date
Requires at least: 2.6.2
Tested up to: 2.6.2
Stable tag: 1.7

Creates events posts for your wordpress. Manipulate it easily and intuitivily. Using the_agenda_loop() you 
generates a $the_event object that have all atributes about the event, use it in combination 
to Agenda::next_events(); or Agenda::events_on();

== Description ==

Now you can create events for your wordpress. Whit this plugin you can create and manipulate events like
you do on Google Calendar. Generate events on the fly creating an agenda of events. The agenda suports a realy
code for repeat events for your satisfaction.

Using `the_agenda_loop()` you generates a $the_event object that have all atributes about the event
Use it in combination to `Agenda::next_events()` or `Agenda::events_on()`

You can also use function `agenda_compromissos()`, `agenda_list()` and `agenda_events()` on your templates. These functions
generate html codes to be used on your tamplate.

== Installation ==

1. Unzip `agenda` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You can configure the category in your Settings.
1. Automaticaly you will see new options in Write and Manage sessions of your wordpress admin.

== Agenda list ==

Generates a list of next events marked in your agenda
Pass your params as wp_string or assigned array:

`
agenda_list('next=5&only_destak=1')
`
is the same as:

`
agenda_list(array('next'=>5,'only_destak'=>true))
`

Params:

1. (int) next         : Number of next events to show (default: 5)
1. (bool) only_destak : If true, show only highlighted events (default: true)
1. (bool) show_date   : If true, show the date of each event (default: true)
1. (string) before    : Put it before each event (default: `<li>`)
1. (string) after     : Put it after each event (default: `</li>`)
1. (bool) print       : Prints the output (default: true)

By default, the function prints the generated string, but you can silencity it and
manipulate the same string. It is returned.

So for use the `agenda_list ()` prints a list contain the next 5 events. While
`agenda_list ('print=0')` does not print, but returns the generated string to your manual manipulation

== Agenda compromissos ==

agenda_compromissos()` can be used on your tamplate's page.
It generates a events list separated by date.
 
Params: `agenda_compromissos ($next, $title_tag, $print)`:

1. `$next` Number of events that must be showed.default: 25
1. `$title_tag` show date tag's name . defaut: `h3`
1. `$print` if matched as `true` prints the result, if it is not matched only returns the string for future manipulations.

== Agenda Events ==

The function `agenda_events()` can be used in your page, listing the events in a year or month.

= Default usage: =

`$defaults = array(
  'month' => date('m'),
  'year' => date('Y'),
  'title_tag' => 'h3',
  'print' => '1'
);` 

= Parameters: =

1. month - if passed, selects a month to show ( default: date('m') )
1. year - selects a yea to show ( default: date('Y') )
1. title_tag - the tag to be used on your title/date tag (default: <h3>)
1. print - prints the result. (default: true)

= Show only events on may 2009 =

`<?php agenda_events('month=5&year=2009') ?>`

The following example shows only events that matches may of 2009.
 
= Show only events on 2008 =

`<?php agenda_events('month=false&year=2008') ?>`

The following example shows only events that matches in all 2008.

= Changing the tag of date events =

`<?php agenda_events('title_tag=h5') ?>`

The following example show events that matches the curent month, changing the tag of title/date by h5.
The output will be:

`
<div class="event_even">
  <h5>09-12-2008</h5>
  <ul>
    <li><a href="http://ldg/?p=12" title="Evento simples">Evento simples</a></li>
    <li><a href="http://ldg/?p=18" title="Event single">Event single</a></li>
    <li><a href="http://ldg/?p=23" title="Multiple Event">Multiple Event</a></li>
  </ul>
</div>
<div class="event_odd">
  <h5>11-12-2008</h5>
  <ul>
    <li><a href="http://ldg/?p=22" title="Algo asi es muy simples">Algo asi es muy simples</a></li>
    <li><a href="http://ldg/?p=18" title="Event single">Event single</a></li>
    <li><a href="http://ldg/?p=28" title="DGmike gones home">DGmike gones home</a></li>
  </ul>
</div>
`

Note that the function generates a even odd class of each day of events

== The Agenda Loop ==

Using the_agenda_loop you generates a $the_event object that have all atributes about the event
Use it in combination to Agenda::next_events(); or Agenda::events_on();

`
  Agenda::events_on('month=5,year=2008');
    while (the_agenda_loop()) {
    print "<h1>{$the_event->what}</h1>";
  }
`

The global object `$the_event` is an standard object like this:

`
stdClass Object (
  [event_id] => 36
  [content] => Um exemplo de <strong>evento simples</strong> com a marcacao necessaria.
  [repeat] => daily
  [repeat_daily] => daily
  [repeat_daily_every] => 3
  [range] => never
  [what] => Meu simples evento
  [excerpt] => Meu excerpt, caso seja necessario.
  [author] => 1
  [pings] => open
  [comments] => open
  [calendar] => Array()
  [where] =>
  [destak] => 0
  [when_start_date] => 2008-09-15
  [when_start_time] => 00:00
  [when_end_date] => 2008-09-16
  [when_end_time] => 00:00
  [all_day] => 1
  [tags] => e mais tag,tag
  [range_ends_until] =>
)
`

== Screenshots ==

1. Create new Event screen
2. An event filled
3. List of events on manipulate events
4. Requesting a delete event

