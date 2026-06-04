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

    <div class="row my-3 align-items-center">
        <nav aria-label="<?php echo lang('Pager.pageNavigation') ?>"
             class="col-md">
            <ul class="pagination align-items-center">
                <?php if ($pager->hasPrevious()) : ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $pager->getFirst() ?>"
                           aria-label="<?php echo lang('Pager.first') ?>">
                            <span aria-hidden="true"><i class="mdi mdi-chevron-double-left"></i></span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $pager->getPreviousPage() ?>"
                           aria-label="<?php echo lang('Pager.previous') ?>">
                            <span aria-hidden="true"><i class="mdi mdi-chevron-left"></i></span>
                        </a>
                    </li>
                <?php endif ?>

                <?php if (count($pager->links()) > 1) : ?>
                    <?php foreach ($pager->links() as $link) : ?>
                        <li class="page-item">
                            <a class="page-link <?php echo $link['active'] ? 'active' : '' ?>"
                               href="<?php echo $link['uri'] ?>">
                                <?php echo $link['title'] ?>
                            </a>
                        </li>
                    <?php endforeach ?>
                <?php endif; ?>

                <?php if ($pager->hasNext()) : ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $pager->getNextPage() ?>"
                           aria-label="<?php echo lang('Pager.next') ?>">
                            <span aria-hidden="true"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $pager->getLast() ?>"
                           aria-label="<?php echo lang('Pager.last') ?>">
                            <span aria-hidden="true"><i class="mdi mdi-chevron-double-right"></i></span>
                        </a>
                    </li>
                <?php endif ?>
            </ul>
        </nav>
        <div class="col-md-2 justify-content-end">
            <span class="align-self-end">
                <?php echo sprintf('Page %s of %s', $pager->getCurrentPageNumber(), counted($pager->getPageCount(), 'Page')) ?>
            </span>
        </div>
    </div>

<?php endif; ?>
