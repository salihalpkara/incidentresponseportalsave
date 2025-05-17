<table id="visitTable" class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Page URL</th>
            <th>Visit Count</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($page_summary as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['url']) ?></td>
                <td><?= htmlspecialchars($row['visit_count']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
