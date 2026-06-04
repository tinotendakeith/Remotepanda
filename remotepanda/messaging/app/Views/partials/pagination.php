<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * Pagination view
 *
 * @var PagerRenderer $pager
 */

if (isset($pager)) :

    helper('inflector');

    $pager->setSurroundCount(2);
    ?>

    <nav aria-label="<?php echo lang('Pager.pageNavigation') ?>"
         class="m-3 d-flex justify-content-between align-items-center">
        <ul class="pagination">
            <?php if ($pager->hasPrevious()) : ?>
                <li>
                    <a class="mx-1 btn btn-light" href="<?php echo $pager->getFirst() ?>"
                       aria-label="<?php echo lang('Pager.first') ?>">
                        <span aria-hidden="true"><i class="mdi mdi-chevron-double-left"></i></span>
                    </a>
                </li>
                <li>
                    <a class="mx-1 btn btn-light" href="<?php echo $pager->getPreviousPage() ?>"
                       aria-label="<?php echo lang('Pager.previous') ?>">
                        <span aria-hidden="true"><i class="mdi mdi-chevron-left"></i></span>
                    </a>
                </li>
            <?php endif ?>

            <?php if (count($pager->links()) > 1) : ?>
                <?php foreach ($pager->links() as $link) : ?>
                    <li>
                        <a class="mx-1 btn btn-light <?php echo $link['active'] ? 'btn-outline-secondary' : '' ?>"
                           href="<?php echo $link['uri'] ?>">
                            <?php echo $link['title'] ?>
                        </a>
                    </li>
                <?php endforeach ?>
            <?php endif; ?>

            <?php if ($pager->hasNext()) : ?>
                <li>
                    <a class="mx-1 btn btn-light" href="<?php echo $pager->getNextPage() ?>"
                       aria-label="<?php echo lang('Pager.next') ?>">
                        <span aria-hidden="true"><i class="mdi mdi-chevron-right"></i></span>
                    </a>
                </li>
                <li>
                    <a class="mx-1 btn btn-light" href="<?php echo $pager->getLast() ?>"
                       aria-label="<?php echo lang('Pager.last') ?>">
                        <span aria-hidden="true"><i class="mdi mdi-chevron-double-right"></i></span>
                    </a>
                </li>
            <?php endif ?>
        </ul>
        <div>
            <?php echo sprintf('Page %s of %s', $pager->getCurrentPageNumber(), counted($pager->getPageCount(), 'Page')) ?>
        </div>
    </nav>

<?php endif; ?>
