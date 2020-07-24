<?php

/**
 * This file contains everything related to the Dump Tool.
 */


/**
 * Dump was designed for the following purpose : having a dump handler that can be useful and forgotten. If your
 * dump is accidentally pushed to prod, users won't see it as it won't be displayed. You can use this class to dump any
 * variable either in the usual format or in json. The default context used by this tool is 'production', for safety
 * reasons. This class wasn't designed to support inheritance : don't make classes inheriting from Dump.
 *
 * @copyright Weborama
 * @author tseillan
 * @version 1.0.0
 */
class Dump
{

    /* @property array $backtrace The backtrace of the dump. */
    protected $backtrace;

    /* @property string $callLocation A string concatenating the file and the line where the dump occurs. */
    private $callLocation = '';

    /* @property string $context The environment in which the dump occurs. Production, dev, preprod... */
    private $context = 'production';

    /* @property Dump $instance Singleton pattern : the instance for the Singleton. */
    private static $instance;

    /**
     * Singleton pattern : the constructor has to be private.
     * @version 1.0.0
     * @since 1.0.0
     */
    private function __construct()
    {

    }

    /**
     * Reset Dump properties on each call. Makes sure to keep object eventhough a static context syntax is used for
     * calls.
     * @version 1.0.0
     * @since 1.0.0
     */
    private function init()
    {

        $this -> context = @APPLICATION_ENV ?  : $this -> context;
        $this -> callLocation = '';
        $this -> backtrace = debug_backtrace ();

    }

    /**
     * Singleton pattern : return the instance of the Singleton, creates it if it doesn't exist.
     * @version 1.0.0
     * @since 1.0.0
     */
    public function getInstance()
    {

        if ( !isset ( self::$instance ) ) {
            self::$instance = new Dump ();
        }
        # Call init method in place of a __construct because the construct is called only once and we might need several Dump Objects.
        self::$instance -> init ();

        return self::$instance;

    }

    /*
    * Displays the backtrace of the dump... in an ugly, unformatted fashion.
    */
    public function getBacktrace()
    {
            echo "<xmp>";
            print_r( $this -> backtrace );
            echo "</xmp>";
    }

    /**
     * Allows commenting on HTML.
     * @todo This method isn't finished yet. See if it's really useful.
     * @version 1.0.0
     * @since 1.0.0
     */
    static public function comment()
    {

        foreach ( func_get_args () as $k => $v ) {
            echo '<!--' . $v . '-->';
        }

    }

    /**
     * Allows the call of a dump while keeping Object context and Singleton Pattern consistency. You can put as many
     * arguments you want to dump. <code><?php Dump::dump( $a_string, $a_boolean, $this );</code>
     * @version 1.0.0
     * @since 1.0.0
     */
    static public function dump()
    {

        // Exclude a backtrace pattern and perform a dump.
        self::getInstance () -> excludeBacktraceEntries ( 'vendor' )
            -> _dump ( func_get_args () );
        return self::getInstance();

    }

    /**
     * Filters backtrace to remove any line that contain an $exlude pattern.
     * @param string $exclude
     * @return Dump
     */
    private function excludeBacktraceEntries( $exclude )
    {

        $this -> backtrace = array_filter( $this -> backtrace, function ( $a ) use ( $exclude ) {
            return ( strpos ( $a['file'], $exclude ) == false );
        });
        sort ( $this -> backtrace );

        return $this;

    }


    /**
     * Get the Location of the call made for the dump. That way you will find the file and the line where a dump is
     * called. Ignores naturally everything related to Dump.
     * @version 1.0.0
     * @since 1.0.0
     */
    private function getCallLocation()
    {

        /* @var \stdClass $directLocation The first location in the backtrace array, usually exactly where your dump was called. */
        $directLocation = (object) array_shift ( $this -> backtrace );

        $this -> callLocation = $directLocation -> file . ':' . $directLocation -> line;

        return $this -> callLocation;

    }


    /**
     * Allows the call of a json dump while keeping Object context and Singleton Pattern consistency. A json bump on an
     * object will only display public properties and methods. <code> <?php Dump::json($aVar); ?> </code>
     * @version 1.0.0
     * @since 1.0.0
     */
    static public function json()
    {

        self::getInstance () -> excludeBacktraceEntries ( 'Dump' )
            -> _json ( func_get_args () );

    }


    /**
     * Handles the normal dump. If the detected context is production, the dump won't be displayed.
     * @param array $args
     * @version 1.0.0
     * @since 1.0.0
     */
    private function _dump( $args )
    {

        if ( $this -> context != 'production' ) {
            echo '<p>[' . $this -> context . ']Dump : ' . $this -> getCallLocation () . '</p>';
            foreach ( $args as $k => $v ) {
                echo '<p>Argument ' . $k, '</p><xmp>';
                var_dump ( $v );
                echo '</xmp>';
            }
            $this -> getBacktrace();
        }

    }


    /**
     * Handle a specific json dump. Won't work if some content has already been sent. Kills any following code.
     * @param array $args
     * @version 1.0.0
     * @since 1.0.0
     */
    private function _json( $args )
    {

        if ( $this -> context != 'production' ) {

            // Set proper header for application/json.
            header ( 'Content-type:application/json' );

            /* @var \stdClass $rtn Provides an object in which the dump will be stored. */
            $rtn = (object) array(
                'location' => $this -> getCallLocation ()
            );

            // Append the variables to dump into the return object.
            foreach ( $args as $k => $v ) {
                $rtn -> {'argument_' . $k} = (object) array(
                    'type' => gettype ( $v ) == 'object' ? get_class ( $v ) : gettype ( $v ),
                    'content' => $v
                );
            }

            // Return the json encoded object with the variables to dump and kills further execution.
            die ( json_encode ( $rtn, JSON_HEX_QUOT ) );
        }

    }

}