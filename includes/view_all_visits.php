<table id="visitTable" class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Visit ID</th>
            <th>Page URL</th>
            <th>IP Address</th>
            <th>Browser</th>
            <th>Visited At</th>
            <th>User ID</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($visit_logs as $visit): ?>
            <tr>
                <td><?= htmlspecialchars($visit['visit_id']) ?></td>
                <td><?= htmlspecialchars($visit['page_url']) ?></td>
                <td><?= htmlspecialchars($visit['ip_address']) ?></td>
                <td><?= htmlspecialchars($visit['browser_name']) ?></td>
                <td><?= htmlspecialchars($visit['visited_at']) ?></td>
                <td><?= htmlspecialchars($visit['user_id'] ?? 'Guest') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
