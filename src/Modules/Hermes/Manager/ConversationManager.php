<?php

namespace App\Modules\Hermes\Manager;

use App\Classes\Database\Database;
use App\Classes\Library\Utils;
use App\Classes\Worker\Manager;
use App\Modules\Hermes\Model\Conversation;

class ConversationManager extends Manager
{
    protected $managerType = '_Conversation';

    public function __construct(
        Database $database,
        protected ConversationUserManager $conversationUserManager,
    ) {
        parent::__construct($database);
    }

    public function load($where = [], $order = [], $limit = [])
    {
        $qr = $this->database->prepare('SELECT c.*
			FROM conversation AS c
			LEFT JOIN conversationUser AS cu
				ON cu.rConversation = c.id
			'.Utils::arrayToWhere($where).'
			GROUP BY c.id
			'.Utils::arrayToOrder($order).'
			'.Utils::arrayToLimit($limit)
        );

        foreach ($where as $v) {
            if (is_array($v)) {
                foreach ($v as $p) {
                    $valuesArray[] = $p;
                }
            } else {
                $valuesArray[] = $v;
            }
        }

        if (empty($valuesArray)) {
            $qr->execute();
        } else {
            $qr->execute($valuesArray);
        }

        $aws = $qr->fetchAll();
        $qr->closeCursor();

        $conversationIds = [0];

        foreach ($aws as $aw) {
            $conversationIds[] = $aw['id'];
        }

        $S_CUM = $this->conversationUserManager->getCurrentSession();
        $this->conversationUserManager->newSession();
        $this->conversationUserManager->load(['c.rConversation' => $conversationIds]);

        foreach ($aws as $aw) {
            $conv = new Conversation();

            $conv->id = $aw['id'];
            $conv->title = $aw['title'];
            $conv->messages = $aw['messages'];
            $conv->type = $aw['type'];
            $conv->dCreation = $aw['dCreation'];
            $conv->dLastMessage = $aw['dLastMessage'];

            for ($i = 0; $i < $this->conversationUserManager->size(); ++$i) {
                if ($this->conversationUserManager->get($i)->rConversation == $conv->id) {
                    $conv->players[] = $this->conversationUserManager->get($i);
                }
            }

            $this->_Add($conv);
        }
        $this->conversationUserManager->changeSession($S_CUM);
    }

    public function save()
    {
        $convs = $this->_Save();

        foreach ($convs as $conv) {
            $qr = $this->database->prepare('UPDATE conversation
				SET
					title = ?,
					messages = ?,
					type = ?,
					dCreation = ?,
					dLastMessage = ?
				WHERE id = ?');
            $aw = $qr->execute([
                    $conv->title,
                    $conv->messages,
                    $conv->type,
                    $conv->dCreation,
                    $conv->dLastMessage,
                    $conv->id,
                ]
            );
        }
    }

    public function add($conv)
    {
        $qr = $this->database->prepare('INSERT INTO conversation
			SET title = ?,
				messages = ?,
				type = ?,
				dCreation = ?,
				dLastMessage = ?'
        );

        $aw = $qr->execute([
                $conv->title,
                $conv->messages,
                $conv->type,
                Utils::now(),
                Utils::now(),
        ]);

        $conv->id = $this->database->lastInsertId();
        $this->_Add($conv);

        return $conv->id;
    }

    public function deleteById($id)
    {
        $qr = $this->database->prepare('DELETE FROM conversation WHERE id = ?');
        $qr->execute([$id]);

        $this->_Remove($id);

        return true;
    }

    public static function count($where = [])
    {
        return 0;
    }
}
