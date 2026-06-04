<style>
    table td:first-child, table td:nth-child(2) {
        width: 1%;
    }
</style>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>&nbsp;</th>
            <th>#</th>
            <th>Name</th>
            <th>Number</th>
            <th>Date</th>
            <th>Age</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($customers ?? [] as $customer): ?>
            <tr>
                <td>
                    <?php echo img_initials($customer->name) ?>
                </td>
                <td>
                    <p><?php echo $customer->id ?></p>
                </td>
                <td>
                    <p><?php echo $customer->name ?></p>
                </td>
                <td>
                    <p><?php echo $customer->mobileNumber ?></p>
                    <?php $normalised = normaliseNumber($customer->mobileNumber) ?>
                    <?php if ($normalised): ?>
                        <small>(<?php echo $normalised ?>)</small>
                    <?php endif; ?>
                </td>
                <td><?php echo $customer->dateOfBirth->toLocalizedString('MMM d'); ?></td>
                <td><?php echo $customer->currentAge ?></td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
</div>