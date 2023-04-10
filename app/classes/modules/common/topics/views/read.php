<?php
$this->layout(
    "main",
    [
        'breadcrumb' =>
            $this->e($entity->forums()->name) .
            '<i class="right angle icon divider"></i> ' .
            $this->e($entity->name)
    ]
)
?>

<div class="ui segment">
    <?php if (!$entity->starred) : ?>
    <form method="post right-post"
        action="<?= $this->e($url->get($url->getSegment(0) . '/follow/' . $url->getSegment(2))) ?>">
        <button class="ui teal labeled icon button">
            <i class="ui star outline icon"></i><?= $this->e($intl('topics.follow')) ?></button>
    </form>
    <?php else : ?>
    <form method="post right-post"
        action="<?= $this->e($url->get($url->getSegment(0) . '/unfollow/' . $url->getSegment(2))) ?>">
        <button class="ui red labeled icon button">
            <i class="ui star icon"></i><?= $this->e($intl('topics.unfollow')) ?></button>
    </form>
    <?php endif ?>
    <h3 class="forum-header header"><?= $this->e($entity->name) ?></h3>
    <div class="ui comments forum-comments">
        <div class="comment">
            <span class="avatar">
                <?php if ($entity->users()->avatar_data) : ?>
                    <img class="ui right spaced avatar image" src="<?= $this->e($entity->users()->avatar_data) ?>">
                <?php else : ?>
                    <i class="ui user icon"></i>
                <?php endif ?>
            </span>
            <div class="content">
                <a class="author"><?= $this->e($entity->users()->name) ?></a>
                <div class="metadata">
                    <span class="date">
                        <?= strtotime($entity->created) ?
                            $this->e($intl->date('long', strtotime($entity->created))) :
                            ''
                        ?>
                    </span>
                </div>
                <div class="text">
                    <?= ($entity->content) ?>
                    <?php
                    $files = [];
                    foreach (array_filter(explode(',', $entity->files)) as $v) {
                        try {
                            $temp = $app->file()->get($v);
                            $files[$url('upload/' . $temp->id() . '/' . $temp->name())] = $temp->name();
                        } catch (\Exception $ignore) {
                        }
                    }
                    if (count($files)) {
                        echo '<div class="ui bulleted list">';
                        foreach ($files as $u => $name) {
                            echo '<a href="' . $this->e($u) . '" class="item">' . $this->e($name) . '</a>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="ui divider"></div>
        <?php foreach ($entity->replies as $comment) : ?>
            <div class="comment forum-comment">
                <?php if ($moderator) : ?>
                    <?php if (!$comment->hidden) : ?>
                    <form method="post right-post"
                        action="<?= $this->e($url->get($url->getSegment(0) . '/hide/' . $comment->post)) ?>">
                        <button class="ui icon button"><i class="ui eye icon"></i></button>
                    </form>
                    <?php else : ?>
                    <form method="post right-post"
                        action="<?= $this->e($url->get($url->getSegment(0) . '/show/' . $comment->post)) ?>">
                        <button class="ui icon button"><i class="ui eye slash icon"></i></button>
                    </form>
                    <?php endif ?>
                <?php endif ?>
                <span class="avatar">
                    <?php if ($comment->users()->avatar_data) : ?>
                        <img class="ui right spaced avatar image" src="<?= $this->e($comment->users()->avatar_data) ?>">
                    <?php else : ?>
                        <i class="ui user icon"></i>
                    <?php endif ?>
                </span>
                <div class="content">
                    <a class="author"><?= $this->e($comment->users()->name) ?></a>
                    <div class="metadata">
                        <span class="date">
                            <?= strtotime($comment->created) ?
                                $this->e($intl->date('long', strtotime($comment->created))) : ''
                            ?>
                        </span>
                    </div>
                    <div class="text">
                        <?= ($comment->content) ?>
                        <?php
                        $files = [];
                        foreach (array_filter(explode(',', $comment->files)) as $v) {
                            try {
                                $temp = $app->file()->get($v);
                                $files[$url('upload/' . $temp->id() . '/' . $temp->name())] = $temp->name();
                            } catch (\Exception $ignore) {
                            }
                        }
                        if (count($files)) {
                            echo '<div class="ui bulleted list">';
                            foreach ($files as $u => $name) {
                                echo '<a href="' . $this->e($u) . '" class="item">' . $this->e($name) . '</a>';
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <?php if (!$entity->locked) : ?>
    <h4 class="ui header"><?= $this->e($intl('topics.reply')) ?></h4>

    <form class="ui form validate-form" method="post">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $form->getField('content')->setValue('')->setOption('label', '');
        $form->getField('files')->setValue([]);
        ?>
        <?= $this->insert(
            'common/form',
            [
                'form' => $form
                    ->enable()
                    ->removeField('forum')
                    ->removeField('name')
                    ->removeField('locked')
                    ->removeField('hidden')
            ]
        ) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
    <?php else : ?>
        <div class="ui warning message"><?= $this->e($intl('topics.locked')) ?></div>
    <?php endif ?>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.right-post { float:right; }
.forum-header { margin:0; }
.forum-comments { max-width:none !important; }
.forum-comments .user.icon { margin:0; }
.forum-comment { border-bottom:1px solid #ccc; padding-bottom:10px; }
</style>