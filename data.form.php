<?php
/**
 * Extra feautures in agenda.php
 * @see Agenda::form() for more details
 */

if (!function_exists('AgendaDataForm')) {
  /**
   * Returns the Labels on Agenda form
   */
  function AgendaDataForm() {
    return array(
      'WRITE_EVENT'       => __('Write Event', 'agenda'),
      'WHAT'              => __('What', 'agenda'),
      'WHEN'              => __('When', 'agenda'),
      'TO'                => __('to', 'agenda'),
      'ALL_DAY'           => __('All day', 'agenda'),
      'REPEATS'           => __('Repeats', 'agenda'),
      'NO_REPEAT'         => __('No repeat', 'agenda'),
      'REPEAT_DAILY'      => __('Daily', 'agenda'),
      'REPEAT_EVERY'      => __('Every', 'agenda'),
      'DAYS'              => __('days', 'agenda'),
      'REPEAT_WEEKLY'     => __('Weekly', 'agenda'),
      'WEEKS'             => __('Weeks', 'agenda'),
      'REPEAT_ON'         => __('Repeat on', 'agenda'),
      'SUNDAY'            => __('S', 'agenda'),
      'MONDAY'            => __('M', 'agenda'),
      'TUESDAY'           => __('T', 'agenda'),
      'WEDNESDAY'         => __('W', 'agenda'),
      'THURSDAY'          => __('T', 'agenda'),
      'FRIDAY'            => __('F', 'agenda'),
      'SATURDAY'          => __('S', 'agenda'),
      'REPEAT_MONTHLY'    => __('Monthly', 'agenda'),
      'MONTHS'            => __('Months', 'agenda'),
      'REPEAT_BY'         => __('Repeat by', 'agenda'),
      'DAY_OF_THE_MONTH'  => __('Day of the month', 'agenda'),
      'DAY_OF_THE_WEEK'   => __('Day of the week', 'agenda'),
      'REPEAT_YEARLY'     => __('Yearly', 'agenda'),
      'YEARS'             => __('Years', 'agenda'),
      'RANGE'             => __('Range', 'agenda'),
      'ENDS'              => __('Ends:', 'agenda'),
      'NEVER'             => __('Never', 'agenda'),
      'UNTIL'             => __('Until', 'agenda'),
      'WHERE'             => __('Where', 'agenda'),
      'CALENDAR'          => __('Calendar', 'agenda'),
      'DESCRIPTION'       => __('Descrption', 'agenda'),
      'TAGS'              => __('Tags', 'agenda'),
      'HOWTO_TAGS'        => __('Separate tags with commas', 'agenda'),
      'EXCERPT'           => __('Excerpt', 'agenda'),
      'AUTHOR'            => __('Author', 'agenda'),
      'DESTAK'            => __('Hightlight this event', 'agenda'),
      'ALLOW_PINGS'       => __('Allow pings', 'agenda'),
      'ALLOW_COMMENTS'    => __('Allow comments', 'agenda'),
      'SAVE'              => __('Save', 'agenda'),
      'PUBLISH'           => __('Publish', 'agenda'),
    );
  }
}

if (!function_exists('AgendaEmptyValuesForm')) {
  /**
   * Returns the empty values for Agenda Form
   */
  function AgendaEmptyValuesForm() {
    return array(
      'v_what'                               => '',
      'v_when_start_date'                    => '',
      'v_when_start_time'                    => '',
      'v_when_end_date'                      => '',
      'v_when_end_time'                      => '',
      'v_all_day'                            => '',
      'v_where'                              => '',
      'v_calendar'                           => array(),
      'content'                              => '',
      'v_tags'                               => '',
      'v_repeat_weekly_on_sunday'            => '',
      'v_repeat_weekly_on_monday'            => '',
      'v_repeat_weekly_on_tuesday'           => '',
      'v_repeat_weekly_on_wednesday'         => '',
      'v_repeat_weekly_on_thursday'          => '',
      'v_repeat_weekly_on_friday'            => '',
      'v_repeat_weekly_on_saturday'          => '',
      'v_repeat_no_repeat'                   => '',
      'v_repeat_daily'                       => '',
      'v_repeat_weekly'                      => '',
      'v_repeat_monthly'                     => '',
      'v_repeat_yearly'                      => '',
      'repeat_monthly_by'                    => '',
      'v_repeat_monthly_by_day_of_the_week'  => '',
      'v_repeat_monthly_by_day_of_the_month' => '',
      'range'                                => '',
      'v_range_never'                        => '',
      'v_range_until'                        => '',
      'v_range_ends_until'                   => '',
      'repeat_weekly_every'                  => '',
      'repeat_daily_every'                   => '',
      'repeat_monthly_every'                 => '',
      'repeat_yearly_every'                  => '',
      'v_excerpt'                            => '',
      'v_pings'                              => 'open',
      'v_comments'                           => 'open',
      'v_destak'                             => 0
    );
  }
}
if (!function_exists('AgendaDataForm')) {
  /**
   * Returns the labels for widget options in sidebar page
   */
  function widgetControl() {
    return array (
      'TITLE'       => __('Title', 'agenda'),
      'NEXT'        => __('Next', 'agenda'),
      'EVENTS'      => __('events', 'agenda'),
      'ONLY_DESTAK' => __('Show only Highlighted Events', 'agenda'),
      'SHOW_DATE'   => __('Show date', 'agenda'),
      'BEFORE'      => __('Before each event', 'agenda'),
      'AFTER'       => __('After each event', 'agenda'),
    );
  }
}
?>