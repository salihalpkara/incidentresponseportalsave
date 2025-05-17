<table id="visitTable" class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>User ID</th>
            <th>Total Visits</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($visits_by_user as $userVisit): ?>
            <tr>
                <td><?= htmlspecialchars($userVisit['user_id']) ?></td>
                <td><?= htmlspecialchars($userVisit['total_visits']) ?></td>
                <td><a href="view_page_visits_user_detail.php?user_id=<?= $userVisit['user_id'] ?>" class="btn btn-sm btn-primary">View Details</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
