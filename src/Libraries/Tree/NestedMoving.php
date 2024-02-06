<?php 
namespace Sygecon\AdminBundle\Libraries\Tree;

use Sygecon\AdminBundle\Libraries\Tree\NestedTab;

final class NestedMoving extends NestedTab 
{
    private const SELECT_NODE = 'id, tree, parent, lft, rgt, level, name, link';

    private const SELECT_TARGET = 'parent, lft, rgt, level, link';

	/**
	 * Перемещает данный узел, чтобы сделать его первым/последним дочерним элементом "parent".
	 * @param integer $id The node to move
	 * @param integer $targetId The node to use as the position marker
	 * @return boolean
     * @access public
	 */
	public function moveNode(int $id = 0, int $targetId = 0): bool 
    {
        if ($targetId == $id) { return false; }
        if (! $row = $this->getRowChilds((int) $id, self::SELECT_NODE)) { return false; }
        if (! $target = $this->getRowChilds((int) $targetId, self::SELECT_TARGET)) { return false; }
        $newpos = ((int) $row->lft < (int) $target->lft ? $target->rgt + 1 : $target->lft);
        if ((int) $row->parent === (int) $target->parent) { unset($target->parent); }
        return $this->move($row, $target, (int) $newpos);
	}

    /**
	 * Перемещает данный узел на определённую позицию в списке.
	 * @param int $id The node to move
	 * @param int $position marker
	 * @return boolean
     * @access public
	 */
	public function moveNodeToPos(int $id = 0, int $position = 0): bool
    {
        if (! $row = $this->getRowChilds((int) $id, self::SELECT_NODE)) { return false; }
        if (! $items = $this->db->query(
            'SELECT ' . self::SELECT_TARGET . ' FROM ' . $this->table . 
            ' WHERE tree = ' . (int) $row->tree . ' AND parent = ' . (int) $row->parent . ' ORDER BY lft ASC'
        )->getResult('object')) { return false; }
        if (! isset($items[$position])) { return false; }

        $target = (object) $items[$position];
        unset($target->parent);
        $count = (int) count($items);
        --$count;
        array_splice($items, 0);
        unset($items);

        if ($position === $count) {
            $newpos = $target->rgt + 1;
        } else if ($position === (int) 0) {
            $newpos = $target->lft;
        } else {
            $newpos = ((int) $row->lft < (int) $target->lft ? $target->rgt + 1 : $target->lft);
        }
        return $this->move($row, $target, (int) $newpos);
	}
    
    // -------------------------------------------------------------------------
	//  MODIFY/REORGANISE TREE
	// -------------------------------------------------------------------------
    // Перемещение узлов
    /**
	 * @param object $node The node old to move
	 * @param object $target The node target
	 * @param int $newpos new left param The node
     * @access public
	 */
    private function move(object $node, object $target, int $newpos): bool 
    {
        if ($node && $target && isset($node->id) && $node->id) {
            $sets = '';
            $tmppos     = (int) $node->lft;
            $width      = (int) ($node->rgt - $tmppos + 1);
            if ($newpos < 1) { $newpos = 1; }

            if (isset($target->parent) && (int) $target->parent !== (int) $node->parent) {
                $sets = 'parent = ' . $target->parent;
                if ((int) $target->level !== (int) $node->level) {
                    $sets = ', level = ' . $target->level;
                    if ($width > 2) { $level = (int) ($target->level - $node->level); }
                }
                if ($target->parent === 0) {
                    $link = '/';  
                    $link .= ($node->name === 'index' ? '' : $node->name);
                } else {
                    $link = $this->setLink($target->link, $node->name);
                }
                if ($link !== $node->link) {
                    $sets .= ', link = "' . $link . '"';
                    $lenLinkOld = strlen($node->link);
                }
            }
            // Обновлем измененные поля
            if ($sets) {
                $this->db->transStart();
                $res = $this->db->query('UPDATE ' . $this->table . ' SET ' . $sets . ' WHERE id = ' . $node->id);
                $this->db->transComplete();

                if (!$res) { return false; }

                if (isset($level)) {
                    $buffer = 'UPDATE ' . $this->table . ' SET level = level';
                    $buffer .= ($level < 0 ? ' - ' : ' + ');
                    $this->db->transStart();
                    $this->db->query($buffer . abs($level) . ' WHERE tree = ' . $node->tree . ' AND lft > ' . $tmppos . ' AND rgt < ' . $node->rgt); 
                    $this->db->transComplete();
                }

                if (isset($lenLinkOld) && $lenLinkOld) {
                    $this->renameLink($node->link, $link);
                    $this->changeLinkInChilds((int) $node->id, (int) $lenLinkOld);

                    $change = false;
                    $buffer = $this->readFileRoute();
                    if ($this->renameUrlToRoute($buffer, $node->link, $link)) { $change = true; }
                    $this->writeFileRoute($buffer, $change);
                    unset($buffer, $link);
                }
            }
                    
            $distance   = (int) ($newpos - $tmppos);
            if ($distance < 0) {
                $distance = $distance - $width;
                $tmppos = $tmppos + $width;
            }
            $this->db->transStart();
            $this->db->query("UPDATE $this->table SET lft = lft + $width WHERE tree = $node->tree AND  lft >= $newpos");
            $this->db->query("UPDATE $this->table SET rgt = rgt + $width WHERE tree = $node->tree AND rgt >= $newpos");
            // -- move subtree into new space
            $this->db->query(
                "UPDATE $this->table SET lft = lft + $distance, rgt = rgt + $distance " .
                "WHERE tree = $node->tree AND lft >= $tmppos AND rgt < $tmppos + $width"
            );
            //  -- remove old space vacated by subtree
            $this->db->query("UPDATE $this->table SET lft = lft - $width WHERE tree = $node->tree AND lft > " . $node->rgt);
            $this->db->query("UPDATE $this->table SET rgt = rgt - $width WHERE tree = $node->tree AND rgt > " . $node->rgt);
            $this->db->transComplete();
            return true;
        }
        return false;
    }
}