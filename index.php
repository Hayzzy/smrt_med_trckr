<?php
$mysqli = new mysqli("localhost", "root", "SHREY526", "SmartMedTrack");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$show_expiry = isset($_GET['expiry']);
$show_supplier = isset($_GET['supplier']);
$show_sales = isset($_GET['sales']);

$query = "SELECT m.med_id, m.name";

if ($show_expiry) {
    $query .= ", b.batch_id, b.expiry_date";
}
if ($show_supplier) {
    $query .= ", s.name AS supplier_name";
}
if ($show_sales) {
    $query .= ", COALESCE(SUM(sales.quantity_sold), 0) AS total_sold, 
                COALESCE(SUM((b.sell_price - b.buy_price) * sales.quantity_sold), 0) AS profit";
}

$query .= " FROM Medicines m";

if ($show_expiry || $show_sales) {
    $query .= " LEFT JOIN Batches b ON m.med_id = b.med_id";
}
if ($show_supplier) {
    $query .= " LEFT JOIN Medicine_Suppliers ms ON m.med_id = ms.med_id
                LEFT JOIN Suppliers s ON ms.supplier_id = s.supplier_id";
}
if ($show_sales) {
    $query .= " LEFT JOIN Sales sales ON sales.batch_id = b.batch_id";
}

$where = [];
if ($search !== '') {
    $search = $mysqli->real_escape_string($search);
    $where[] = "m.name LIKE '%$search%'";
}
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " GROUP BY m.med_id";
if ($show_expiry) {
    $query .= ", b.batch_id";
}
if ($show_supplier) {
    $query .= ", s.name";
}

$result = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TrackMedz</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body class="bg-[#0f172a] text-white">
  <div class="max-w-7xl mx-auto p-6">
    <header class="flex items-center justify-between py-4 border-b border-teal-600">
      <div class="flex items-center gap-3">
        <span class="text-2xl font-bold text-teal-400"> TRCKMEDz</span>
        <span class="text-sm text-gray-400 font-light">/ Medicine Info Tool</span>
      </div>
      <form method="GET" class="flex gap-4">
        <input type="text" name="search" placeholder="Search medicine..." value="<?php echo htmlspecialchars($search); ?>" class="bg-[#1e293b] border border-teal-500 rounded px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
        <button type="submit" class="bg-teal-500 hover:bg-teal-600 px-4 py-2 rounded font-semibold">Search</button>
      </form>
    </header>

    <form method="GET" class="mt-6 flex flex-wrap gap-4">
      <label class="inline-flex items-center">
        <input type="checkbox" name="expiry" <?php if ($show_expiry) echo 'checked'; ?> class="form-checkbox text-teal-500">
        <span class="ml-2">Show Expiry</span>
      </label>
      <label class="inline-flex items-center">
        <input type="checkbox" name="supplier" <?php if ($show_supplier) echo 'checked'; ?> class="form-checkbox text-teal-500">
        <span class="ml-2">Show Supplier</span>
      </label>
      <label class="inline-flex items-center">
        <input type="checkbox" name="sales" <?php if ($show_sales) echo 'checked'; ?> class="form-checkbox text-teal-500">
        <span class="ml-2">Show Sales</span>
      </label>
      <button type="submit" class="ml-auto bg-teal-500 hover:bg-teal-600 px-4 py-2 rounded font-semibold">Apply Filters</button>
    </form>

    <div class="mt-8 overflow-x-auto">
      <table class="min-w-full bg-[#1e293b] rounded-lg overflow-hidden">
        <thead>
          <tr class="bg-teal-600 text-left">
            <th class="px-6 py-3 font-semibold">Medicine</th>
            <?php if ($show_expiry): ?>
              <th class="px-6 py-3 font-semibold">Batch ID</th>
              <th class="px-6 py-3 font-semibold">Expiry Date</th>
            <?php endif; ?>
            <?php if ($show_supplier): ?>
              <th class="px-6 py-3 font-semibold">Supplier</th>
            <?php endif; ?>
            <?php if ($show_sales): ?>
              <th class="px-6 py-3 font-semibold">Total Sold</th>
              <th class="px-6 py-3 font-semibold">Profit (₹)</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#334155]">
          <?php while ($row = $result->fetch_assoc()) { ?>
          <tr>
            <td class="px-6 py-4"><?php echo $row['name']; ?></td>
            <?php if ($show_expiry): ?>
              <td class="px-6 py-4"><?php echo $row['batch_id'] ?? '-'; ?></td>
              <td class="px-6 py-4"><?php echo $row['expiry_date'] ?? '-'; ?></td>
            <?php endif; ?>
            <?php if ($show_supplier): ?>
              <td class="px-6 py-4"><?php echo $row['supplier_name'] ?? '-'; ?></td>
            <?php endif; ?>
            <?php if ($show_sales): ?>
              <td class="px-6 py-4"><?php echo $row['total_sold'] ?? '0'; ?></td>
              <td class="px-6 py-4">₹<?php echo number_format($row['profit'] ?? 0, 2); ?></td>
            <?php endif; ?>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
