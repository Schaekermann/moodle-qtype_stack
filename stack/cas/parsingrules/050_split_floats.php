<?php

require_once(__DIR__ . '/filter.interface.php');

/**
 * AST filter that removes floats that have the "e" in them. Will tag
 * the new stars with the 'insertstars' position marker, and adds
 * 'missing_stars' to the answernote.
 *
 * Note that in cases like '1.23e-4' or '5.6E+7' only adds one star and
 * turns that -/+ to an op.
 */
class stack_ast_filter_split_floats_050 implements stack_cas_astfilter {
    public function filter(MP_Node $ast, array &$errors, array &$answernotes): MP_Node {
        $process = function($node) use (&$answernotes) {
            if ($node instanceof MP_Float && $node->raw !== null) {
                $replacement = false;
                if (strpos($node->raw, 'e') !== false) {
                    $parts = explode('e', $node->raw);
                    if (strpos($parts[0], '.') !== false) {
                        $replacement = new MP_Operation('*', new MP_Float(floatval($parts[0]), $parts[0]),
                                new MP_Operation('*', new MP_Identifier('e'), new MP_Integer(intval($parts[1]))));
                    } else {
                        $replacement = new MP_Operation('*', new MP_Integer(intval($parts[0])),
                                new MP_Operation('*', new MP_Identifier('e'), new MP_Integer(intval($parts[1]))));
                    }
                    $replacement->position['insertstars'] = true;
                    if ($parts[1]{0} === '-' || $parts[1]{0} === '+') {
                        // 1e+1...
                        $op = $parts[1]{0};
                        $val = abs(intval($parts[1]));
                        $replacement = new MP_Operation($op, new MP_Operation('*', $replacement->lhs,
                                new MP_Identifier('e')), new MP_Integer($val));
                        $replacement->lhs->position['insertstars'] = true;
                    }
                } else if (strpos($node->raw, 'E') !== false) {
                    $parts = explode('E', $node->raw);
                    if (strpos($parts[0], '.') !== false) {
                        $replacement = new MP_Operation('*', new MP_Float(floatval($parts[0]), $parts[0]),
                                new MP_Operation('*', new MP_Identifier('E'), new MP_Integer(intval($parts[1]))));
                    } else {
                        $replacement = new MP_Operation('*', new MP_Integer(intval($parts[0])),
                                new MP_Operation('*', new MP_Identifier('E'), new MP_Integer(intval($parts[1]))));
                    }
                    $replacement->position['insertstars'] = true;
                    if ($parts[1]{0} === '-' || $parts[1]{0} === '+') {
                        // 1.2E-1...
                        $op = $parts[1]{0};
                        $val = abs(intval($parts[1]));
                        $replacement = new MP_Operation($op, new MP_Operation('*', $replacement->lhs,
                                new MP_Identifier('E')), new MP_Integer($val));
                        $replacement->lhs->position['insertstars'] = true;
                    }
                }
                if ($replacement !== false) {
                    $answernotes[] = 'missing_stars';
                    $node->parentnode->replace($node, $replacement);
                    return false;
                }
            }
            return true;
        };
        // @codingStandardsIgnoreStart
        while ($ast->callbackRecurse($process) !== true) {
        }
        // @codingStandardsIgnoreEnd
        return $ast;
    }
}