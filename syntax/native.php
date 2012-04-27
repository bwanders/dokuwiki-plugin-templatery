<?php
/**
 * DokuWiki Plugin skeleton (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_templatery_native extends DokuWiki_Syntax_Plugin {
    protected $helper;

    public function __construct() {
        $this->helper =& plugin_load('helper','templatery');
    }

    public function getName() {
        return false;
    }

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

    public function handle($match, $state, $pos, &$handler){
        return array();
    }

    protected function isPreview() {
        return !$this->helper->isDelegating();
    }

    protected function hasField($field) {
        if($this->isPreview()) return true;
        return $this->helper->hasField($field);
    }

    protected function displayField($mode, &$R, $field, $default) {
        $fielder = new syntax_plugin_templatery_field();
        return $fielder->render($mode, $R, array($field, $default));
    }

    protected function includeTemplate($id, $variables) {
        $includer = new syntax_plugin_templatery_include();
        return $includer->render($mode, $R, array($id, $variables, array(false) ));
    }

    public function render($mode, &$R, $data) {
        return false;
    }
}

// vim:ts=4:sw=4:et:
