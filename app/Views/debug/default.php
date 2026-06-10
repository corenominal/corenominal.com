<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="border-bottom border-1 mb-4 pb-2">
                <h1 class="h2">
                    <a href="/debug"><?= $title ?></a> / <a href="./"><?= $class ?></a> / <?= $function ?>
                </h1>
            </div>

            <?php if(isset($dump)): ?>

                <p>Contents of <code>$dump</code>:</p>

                <?php
                    function dump($dump)
                    {
                        array_map(
                            static function ($dump) {
                                echo '<pre><code>';
                                var_dump($dump);
                                echo '</code></pre>';
                            },
                            func_get_args()
                        );
                    }
                    dump($dump);
                ?>

            <?php endif; ?>

            <?php if(isset($html)): ?>

                <p>Contents of <code>$dump</code>:</p>

                <div class="border p-4">
                    <?= $html ?>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<?= $this->endSection() ?>