<?php
/**
 * DokuWiki Plugin Templatery (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * Base class for native template implementations.
 * 
 * A native template implementation is supported through a set of helper
 * methods in this base class.
 */
class syntax_plugin_templatery_native extends DokuWiki_Syntax_Plugin {
    protected $helper;

    public function __construct() {
        $this->helper =& plugin_load('helper','templatery');
    }
    
    /**
     * The name of the native template. This is used to determine the
     * required syntax. (i.e., the syntax is @@->name@@).
     */ 
    protected function getName() {
        return false;
    }

    /**
     * Tells you whether this is a preview or the real deal.
     */
    protected function isPreview() {
        return !$this->helper->isDelegating();
    }

    /**
     * Tells yout whether the field is defined. All fields are defined
     * when previewing.
     */
    protected function hasField($field) {
        if($this->isPreview()) return true;
        return $this->helper->hasField($field);
    }

    /**
     * Displays a field. Also works in preview mode.
     */
    protected function displayField($mode, &$R, $field, $default) {
        $fielder = new syntax_plugin_templatery_field();
        return $fielder->render($mode, $R, array($field, $default));
    }

    /**
     * Includes another template. Also works in preview mode.
     */
    protected function includeTemplate($id, $variables) {
        $includer = new syntax_plugin_templatery_include();
        return $includer->render($mode, $R, array($id, $variables, array(false) ));
    }

    public function handle($match, $state, $pos, &$handler){
        return array();
    }
   
    public function render($mode, &$R, $data) {
        return false;
    }

    // Only override these if you know what you're doing:

    public function getType() {
        return 'templated';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 200;
    }

    public function connectTo($mode) {
        if($this->getName() !== false) {
            $this->Lexer->addSpecialPattern('@@->'.$this->getName().'@@', $mode, substr(get_class($this),7));
        }
    }
}

