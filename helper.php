<?php
/**
 * DokuWiki Plugin templatery (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class helper_plugin_templatery extends DokuWiki_Plugin {
    /**
     * Resolves a template identifier.
     * 
     * @param id string the template identifier
     * @param exists boolean will be set to whether the page existed or not
     * @return an array containing the resolved page and normalized template id
     */
    public function resolveTemplate($id, &$exists) {
        list($page, $hash) = explode('#',$id,2);
        if(empty($hash)) $hash = '';

        $hash = $this->cleanTemplateId($hash);

        // use configured namespace as resolve base for template finding
        resolve_pageid(cleanID($this->getConf('template_namespace')), $page, $exists);

        return array($page, $hash);
    }

    /**
     * Cleans a template identifier.
     *
     * @param id string the identifier
     * @return a cleaned identifier
     */
    public function cleanTemplateId($id) {
        return str_replace(array(':','.'),'',cleanID($id));
    }

    /**
     * Loads a template.
     * 
     * @return an array of instructions, or null if the template could not be made available
     */
    public function loadTemplate($page, $hash, $sectioning = array(false)){
        if(!page_exists($page,'',false)) {
            return null;
        }

        // load template
        $instructions = p_cached_instructions(wikiFN($page),$page);

        // fetch sectioning data
        list($section, $level) = $sectioning;

        // the result
        $template = null;

        // now we mangle all instructions to end up with a clean and nestable list of instructions
        $inTemplate = false;
        $closedSection = false;
        for($i=0;$i<count($instructions);$i++) {
            $ins = $instructions[$i];

            // we encounter a <template>
            if($ins[0]=='plugin' && $ins[1][0]=='templatery_wrapper' && $ins[1][1][0] == DOKU_LEXER_ENTER && (empty($hash) || $ins[1][1][1] == $hash)) {
                $inTemplate = true;
                $template = array();
                continue;
            }

            // we encounter the first header while we're being included in a section
            if($inTemplate && !$closedSection && $ins[0]=='plugin' && $ins[1][0]=='templatery_header') {
                // close the section
                $template[] = array('plugin',array('templatery_section',array('conditional_close'),0,''),$ins[2]);
                $closedSection = true;
            }

            // we encounter a </template>
            if($inTemplate && $ins[0]=='plugin' && $ins[1][0]=='templatery_wrapper' && $ins[1][1][0] == DOKU_LEXER_EXIT) {
                if($closedSection) {
                    $template[] = array('plugin',array('templatery_section',array('conditional_open'),0,''),$ins[2]);
                }
                break;
            }

            // all other instructions
            if($inTemplate) {
                switch($ins[0]) {
                    // replace section_close and section_open with templatery-aware versions
                    case 'section_open': $template[] = array('plugin',array('templatery_section',array('open',$ins[1][0]),0,''),$ins[2]); break;

                    case 'section_close': $template[] = array('plugin',array('templatery_section',array('close'),0,''),$ins[2]); break;

                    // replace conditional with nested version
                    case 'plugin': switch($ins[1][0]) {
                        case 'templatery_conditional':
                            // store variable to determine  on
                            $variable = $ins[1][1][2];
                            $negation = $ins[1][1][1];

                            // nest the list of instructions
                            $nested = array();
                            $i++;
                            while(!($instructions[$i][0] == 'plugin' && $instructions[$i][1][0] == 'templatery_conditional')) {
                                $nested[] = $instructions[$i];
                                $i++;
                            }

                            // add a conditional instruction with nested list
                            $template[] = array('plugin', array('templatery_conditional',array('conditional', $negation, $variable, $nested),0,''),$instruction[$i][2]);
                            break;

                        default: $template[] = $ins; break;
                    }
                    break;

                    // any other instruction goes straight into the list
                    default: $template[] = $ins; break;
                }
            }
        }

        // return the template, if any
        return $template;
    }

    /**
     * Stack of opened templates.
     */
    private static $templates = array();

    /**
     * Pushes a new template onto the stack.
     */
    public function pushTemplate($page, $hash) {
        array_push(self::$templates, "$page#$hash");
    }

    /**
     * Pops a template from the stack.
     */
    public function popTemplate() {
        array_pop(self::$templates);
    }

    /**
     * Checks whether the given template can be opened. This
     * takes into account the maximum recursion depth, and
     * allows for self-recursive templates.
     */
    public function isTemplateAllowed($page, $hash) {
        // determine maximum value
        $max = intval($this->getConf('maximum_nesting'));

        // check depth of current nesting
        return count(self::$templates) < $max;
    }

    /**
     * The stack of value delegates.
     */
    private static $delegates = array();

    /**
     * Should we delegate or preview?
     */
    public function isDelegating() {
        return count(self::$delegates) > 0;
    }
    
    /**
     * Gets the idx'th stack delegate from stack top.
     */
    public function getDelegate($idx=0) {
        $idx = count(self::$delegates)-1-$idx;

        return isset(self::$delegates[$idx]) ? self::$delegates[$idx] : null;
    }

    /**
     * Renders a template.
     *
     * @param template array the template to render
     * @param delegate object the delegate to use for replacements
     * @param R object the renderer to use
     */
    public function applyTemplate(&$template, &$delegate, &$R) {
        array_push(self::$delegates, $delegate);
        $R->nest($template);
        array_pop(self::$delegates);
    }

    /**
     * Delegate hasField.
     */
    public function hasField($field) {
        return $this->getDelegate()->hasField($field);
    }

    /**
     * Delegate getField.
     */
    public function getField($mode, &$R, $field, $default) {
        return $this->getDelegate()->getField($mode, $R, $field, $default);
    }

    /**
     * Delegate displayField.
     */
    public function displayField($mode, &$R, $field, $default) {
        return $this->getDelegate()->displayField($mode, $R, $field, $default);
    }

    /**
     * The section stack. This stack is used for the
     * on the fly fixing of sections within templates.
     */
    private static $sections = array();

    /**
     * Pushes a section onto the stack.
     */
    public function pushSection($level) {
        array_push(self::$sections,$level);
    }

    /**
     * Pops a section from the stack.
     */
    public function popSection() {
        array_pop(self::$sections);
    }

    /**
     * Peeks at the current top section.
     */
    public function peekSection() {
        return end(self::$sections);
    }
}

// vim:ts=4:sw=4:et:
