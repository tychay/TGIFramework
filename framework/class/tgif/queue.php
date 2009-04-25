<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_queue}
 * 
 * @package tgiframewrok
 * @subpackage global
 * @copyright c.2007-2009 Tagged, Inc., c.2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{ tgif_queue
// docs {{{
/**
 * Handling PHP deferred events and publish-subscribe.
 *
 * This is implemented using a somewhat complicated observer pattern.
 * On a simple level, objects register themselves to listen in on certain
 * events (subscribe). When events occur, they send notifications to this object
 * (pubish) when then activates the listeners.
 *
 * Note that more important than one-to-many relationship of publish-subscribe,
 * the queue system allows for Inversion Of Control. By this I mean that lower
 * level objects should publish events about themselves and then higher level
 * objects should "subscribe" to notifications on those events. This way, the
 * lower level objects need not know about the higher level objects and their
 * implementation and are thus not dependent on their existence to work (as
 * is required currently).
 *
 * The complexity in design comes in from the following needs
 * - memory: we should be able to register listeners without having to register
 *   the entire object. This way, if the event never gets called, the memory
 *   usage to activate them won't be needed. Similarly we should be able to
 *   load the lookup on events as the events are created to minimize the memory
 *   footprint in case the event is never created.
 * - event classes: we should be able to listen to entire groups of events
 *   instead of having to register to listen to each event as they come in.
 *   However, due to "memory" considerations above, we don't want to subscribe
 *   to every event individually.
 * - event queues: Event classes should be divided into different event
 *   categories such as the page, memcache, and db events.
 * - arbitrary depths: different queues might have a different depth of classes
 *   and different listeners may wish to subscribe at different levels within
 *   the same queue.
 * - priority: some listeners need to act quicker than others
 * - extra data: for the "one strange thing" below, subscribers may need extra
 *   data provided back to it that is not in the event as well as if a listener
 *   listens in on many events, it needs to introspect the event to know what
 *   was in it.
 *
 * One strange thing you might consider is we don't need to consider if the
 * subscriber needs to activate on page shutdown on an event. Instead, in that
 * case the subscriber listens to the main event and then on notification, it
 * will register ANOTHER subscriber to listen for the shutdown event.
 *
 * QUEUE: object tgif_page
 * You can register the handler simply by using priority.
 * - (now)  - implied, don't use this event.
 * - render - when the tgif_page::render() is called
 * - output - right before or after page render (priority == 1)
 *
 * QUEUE: shutdown
 * This is called when the page shuts down. Ideally this would be just before
 * and just after the connection is closed, but PHP no longer allows that to
 * be done. If you need to queue things "for realzies" then you should proxy
 * that through a service.
 *
 * DESIGN CAVEAT:
 * I am well aware that this could be refactored to be more OOP, but I don't
 * want it this way because objects are slow in PHP. Basically, we have a single
 * object in the design and a single interface and just take advantge of PHP's
 * native constructs for the rest.
 */
// }}}
class tgif_queue
{
    // {{{ - $_listeners
    /**
     * This is the queue of event classes and the listeners that are registered
     * to each. The primary index at this level are the queues.
     *
     * The listing of subscribers at a given level are stored in the hash
     * "_registration".
     *
     * Each subscriber is a hash containing:
     * - callback: the callback function to call
     * - priority: the sort priority of the queue
     * - passEvent: this can be false for the rare instances where the callback
     *      expects a void. (built-in callbacks)
     *
     * @var $_listeners array
     */
    private $_listeners = array();
    // }}}
    // {{{ - publish($eventChannel[,$eventData])
    /**
     * Handle event publication.
     *
     * In order to minimize the memory footprint, the subscribers are loaded
     * at event runtime. To determine if they have already been loaded,
     * we check to see if an array in the {@link $_listeners} stack has
     * already been created and attempt to load the queue for it if it
     * hasn't.
     *
     * Note that all the possible listeners are gathered, then sorted by
     * priority (ascending) and then called in turn.
     *
     * @param $eventChannel array|string If it is a string, it will split into
     *      an array. This is the queue channel the event occured in.
     * @param $eventData array the event itself which is an array. The only
     *      data missing is the reserved word "event" which is automatically
     *      added to the event.
     */
    function publish($eventChannel, $event=array())
    {
        $listeners = array();
        // collect registrations {{{
        $queue_classes = (is_array($eventChannel))
                       ? $eventChannel
                       : explode(' ', $eventChannel);
        // make sure the event queues have been loaded
        $this->_loadPrequeue($queue_classes);
        // tack on event name to the event
        $event['event'] = implode(' ',$queue_classes);
        $pos =& $this->_listeners;
        foreach ($queue_classes as $class) {
            // no more events
            //if (!array_key_exists($class, $pos)) { break; }
            $pos =& $pos[$class];
            // no registrered listeners at this level (shouldn't happen but does)
            if (!array_key_exists('_registrations', $pos)) { error_log(sprintf('%s: unset class! %s', get_class($this), implode(' ',$class))); continue; }
            foreach ($pos['_registrations'] as &$listener) {
                $listeners[] =& $listener;
            }
        }
        // }}}
        usort($listeners,array('tgif_queue','priority_cmp'));
        foreach ($listeners as &$listener) {
            // notify the listener {{{
            if ($listener['passEvent']) {
                call_user_func($listener['callback'],$event);
            } else {
                call_user_func($listener['callback']);
            }
            // }}}
            // handle clearing of listener {{{
            if (!$listener['keepAlive']) {
                unset($listener);
            }
            // }}}
        }
        // notify outputcache
        //tgif_outputcache::handle_event($event);
    }
    // }}}
    // {{{ - subscribe($eventChannel,$callback[,$priority,$passEvent,$keepAlive])
    /**
     * Handle event publication.
     *
     * You may be wondering why you shouldn't just call this subscription
     * function directly in the object itself? This is fine too, but you should
     * be warned that this might never be called because the queueing system
     * works <em>even if the object classfile is never loaded!</em> wheras
     * putting it in the object file assumes that it will be included before
     * the event gets triggered.
     *
     * Because this can be called with prebinding on event prequeues that
     * haven't been loaded from the sharedmem cache, the act of late binding
     * forces the creation of these queues.
     *
     * @param $eventChannel array|string If it is a string, it will split into
     *      an array. This is the queue channel the event is in.
     * @param $priority integer the ordering the listeners will be called when
     *      the event gets triggered (ascending)
     * @param $passEvent boolean Set this to false in the rare case the callback
     *      should not have anything passed to it. this is a rare default-true.
     */
    function subscribe($eventChannel, $callback, $priority=0, $passEvent=true, $keepAlive=true)
    {
        // load prequeue {{{
        $queue_classes = (is_array($eventChannel))
                       ? $eventChannel
                       : explode(' ', $eventChannel);
        $this->_loadPrequeue($queue_classes);
        // }}}
        // seek to insert position {{{
        $pos =& $this->_listeners;
        foreach($queue_classes as $class) {
            $pos =& $pos[$class];
        }
        $pos =& $pos['_registrations'];
        // }}}
        $pos[] = array(
                'callback'  => $callback,
                'priority'  => $priority,
                'passEvent' => $passEvent,
                'keepAlive' => $keepAlive,
            );
    }
    // }}}
    // {{{ - _loadPrequeue($queueClasses)
    /**
     * Makes sure the pre-queue for a queue class has been loaded.
     *
     * Because this is called from both {@link publish()} and
     * {@link subscribe()}, I abstracted this out into a private method. A side
     * benefit of this function is that all the listeners in provided queueClass
     * will be set.
     *
     * The prequeue is labeled thusly:
     *      _q-<event class>-<event_name>
     * where the "-" replaces the " ". FOr instance:
     *      _q-object-tgif_page-output
     * corresponds to [object][tgif_page][output]
     * This consists of an array of all the listeners that have subscribed
     * to this system.
     *
     * @param $queueClasses array This is the queue channel the event is in.
     * @param $pos array the position of the queueClasses.
     */
    function _loadPrequeue($queueClasses)
    {
        global $_TAG;
        $this_prequeue = '_q';
        $pos =& $this->_listeners;
        foreach($queueClasses as $class) {
            $this_prequeue .= '-'.$class;
            // if prequeue not registered, load it {{{
            if (!array_key_exists($class,$pos)) {
                $pos[$class] = array();
                if ($data = $_TAG->config($this_prequeue)) {
                    // bind default values {{{
                    foreach ($data as &$listener) {
                        if (!array_key_exists('priority',$listener)) {
                            $listener['priority'] = 0;
                        }
                        if (!array_key_exists('passEvent',$listener)) {
                            $listener['passEvent'] = true;
                        }
                        if (!array_key_exists('keepAlive',$listener)) {
                            $listener['keepAlive'] = true;
                        }
                    }
                    // }}}
                    $pos[$class]['_registrations'] = $data;
                } else {
                    $pos[$class]['_registrations'] = array();
                }
            }
            // }}}
            $pos =& $pos[$class];
        }
    }
    // }}}
    // {{{ + priority_cmp($a,$b)
    /**
     * Comparison function for listeners registered under priority compare.
     *
     * The smaller number is first.
     */
    static function priority_cmp($a,$b)
    {
        if ($a['priority'] == $b['priority']) { return 0; }
        return ($a['priority'] < $b['priority']) ? -1 : 1;
    }
}
// }}}
// }}}
?>
