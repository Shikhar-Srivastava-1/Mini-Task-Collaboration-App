<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/db.php';

$stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'Admin') {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$_SESSION['user_id'] = $user['id'];
$name = $user['name'];

// Fetch all users for admin view
$stmt = $pdo->prepare("SELECT id, name, email, role FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/heroicons@2.0.18/dist/heroicons.min.css" rel="stylesheet">
    <style>
        .navcontainer {
            transition: transform 0.3s ease-in-out;
        }
        .nav-hidden {
            transform: translateX(-100%);
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .description-section {
            transition: max-height 0.3s ease-in-out;
            max-height: 0;
            overflow: hidden;
        }
        .description-section.open {
            max-height: 400px;
        }
        @media (min-width: 640px) {
            .navcontainer {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">
    <!-- Header -->
    <header class="bg-white shadow-sm h-14 w-full fixed top-0 z-50 flex items-center justify-between px-4">
        <div class="flex items-center space-x-3">
            <button id="hamburger" class="sm:hidden">
                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
            </button>
            <div class="text-xl font-semibold text-teal-500">Admin Portal</div>
        </div>
    </header>

    <div class="pt-14 min-h-[calc(100vh-3.5rem)]">
        <!-- Sidebar -->
        <div class="navcontainer w-60 h-[calc(100vh-3.5rem)] bg-white shadow-md fixed top-14 left-0 no-scrollbar overflow-y-auto nav-hidden sm:nav-visible">
            <nav class="flex flex-col h-full p-3">
                <div class="space-y-1">
                    <a href="../profile" class="flex items-center space-x-2 p-2 hover:bg-gray-100 rounded-md transition-colors">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-4 7c-3.313 0-6 2.687-6 6h12c0-3.313-2.687-6-6-6z" />
                        </svg>
                        <h3 class="text-sm font-medium"><?php echo htmlspecialchars($name); ?></h3>
                    </a>
                    <div class="flex items-center space-x-2 p-2 bg-indigo-600 text-white rounded-md">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <h3 class="text-sm font-medium">Dashboard</h3>
                    </div>
                    <a href="../logout.php" class="flex items-center space-x-2 p-2 hover:bg-gray-100 rounded-md transition-colors">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <h3 class="text-sm font-medium">Log Out</h3>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="sm:ml-60 p-3">
            <div class="max-w-4xl mx-auto bg-white rounded-md shadow-xs p-3">
                <h1 class="text-lg font-semibold text-indigo-700 mb-3">All Tasks</h1>

                <!-- Notification Banner -->
                <div id="reminder-banner" class="hidden bg-amber-50 border-l-2 border-amber-600 text-amber-800 p-2 mb-3 rounded text-xs"></div>

                <!-- Task Form -->
                <form id="task-form" class="mb-4 space-y-2">
                    <input type="hidden" id="task-id">
                    <div>
                        <label for="title" class="block text-xs font-medium text-gray-700">Title</label>
                        <input type="text" id="title" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                    </div>
                    <div class="flex flex-col sm:flex-row sm:space-x-3 space-y-2 sm:space-y-0">
                        <div class="flex-1">
                            <label for="deadline" class="block text-xs font-medium text-gray-700">Deadline</label>
                            <input type="date" id="deadline" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                        </div>
                        <div class="flex-1">
                            <label for="priority" class="block text-xs font-medium text-gray-700">Priority</label>
                            <select id="priority" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                    </div>
                    <div id="status-field" class="hidden">
                        <label for="status" class="block text-xs font-medium text-gray-700">Status</label>
                        <select id="status" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="submit" id="submit-task" class="bg-indigo-600 text-white px-3 py-1 rounded-md hover:bg-indigo-700 transition-colors text-xs">Create Task</button>
                        <button type="button" id="cancel-edit" class="hidden bg-gray-200 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-300 transition-colors text-xs">Cancel</button>
                    </div>
                </form>
                <div id="form-message" class="text-xs mb-3"></div>

                <!-- Sort and Filters -->
                <div class="mb-3 flex flex-col sm:flex-row sm:space-x-3 space-y-2 sm:space-y-0">
                    <div class="flex-1">
                        <label for="sort-by" class="block text-xs font-medium text-gray-700">Sort By</label>
                        <select id="sort-by" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            <option value="deadline">Deadline</option>
                            <option value="title">Title</option>
                            <option value="creator">Creator</option>
                            <option value="priority">Priority</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="sort-order" class="block text-xs font-medium text-gray-700">Order</label>
                        <select id="sort-order" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            <option value="asc">Ascending</option>
                            <option value="desc">Descending</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="filter-status" class="block text-xs font-medium text-gray-700">Status</label>
                        <select id="filter-status" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            <option value="">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="filter-priority" class="block text-xs font-medium text-gray-700">Priority</label>
                        <select id="filter-priority" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            <option value="">All</option>
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="filter-deadline" class="block text-xs font-medium text-gray-700">Deadline</label>
                        <select id="filter-deadline" class="mt-0.5 block w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1.5 px-2">
                            <option value="">All</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                        </select>
                    </div>
                </div>

                <!-- Task List -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creator</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="task-list" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>

                <!-- Users List -->
                <div class="mt-6">
                    <h1 class="text-lg font-semibold text-indigo-700 mb-3">All Users</h1>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="user-list" class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="px-3 py-2 text-xs text-gray-900"><?php echo htmlspecialchars($u['id']); ?></td>
                                        <td class="px-3 py-2 text-xs text-gray-900"><?php echo htmlspecialchars($u['name']); ?></td>
                                        <td class="px-3 py-2 text-xs text-gray-900"><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td class="px-3 py-2 text-xs text-gray-900"><?php echo htmlspecialchars($u['role']); ?></td>
                                        <td class="px-3 py-2 text-xs font-medium">
                                            <?php if ($u['id'] != $user['id']): ?>
                                                <a href="#" class="text-red-600 hover:text-red-800 delete-user" data-id="<?php echo $u['id']; ?>">
                                                    <svg class="h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400">Self</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle
        const hamburger = document.getElementById('hamburger');
        const nav = document.querySelector('.navcontainer');
        hamburger.addEventListener('click', () => {
            nav.classList.toggle('nav-hidden');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth < 640 && !nav.contains(e.target) && !hamburger.contains(e.target)) {
                nav.classList.add('nav-hidden');
            }
        });

        document.addEventListener('contextmenu', (e) => {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
            }
        });

        // Task Management
        const taskForm = document.getElementById('task-form');
        const submitTask = document.getElementById('submit-task');
        const cancelEdit = document.getElementById('cancel-edit');
        const formMessage = document.getElementById('form-message');
        const taskList = document.getElementById('task-list');
        const reminderBanner = document.getElementById('reminder-banner');
        const statusField = document.getElementById('status-field');
        const filterStatus = document.getElementById('filter-status');
        const filterPriority = document.getElementById('filter-priority');
        const filterDeadline = document.getElementById('filter-deadline');
        const sortBy = document.getElementById('sort-by');
        const sortOrder = document.getElementById('sort-order');
        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

        function resetForm() {
            taskForm.reset();
            document.getElementById('task-id').value = '';
            submitTask.textContent = 'Create Task';
            cancelEdit.classList.add('hidden');
            statusField.classList.add('hidden');
            formMessage.textContent = '';
        }

        async function fetchTasks() {
            const params = new URLSearchParams({
                status: filterStatus.value,
                priority: filterPriority.value,
                deadline: filterDeadline.value,
                sort_by: sortBy.value,
                sort_order: sortOrder.value
            });
            const response = await fetch(`tasks.php?${params}`);
            const data = await response.json();
            
            if (response.ok) {
                taskList.innerHTML = '';
                data.tasks.forEach(task => {
                    const row = document.createElement('tr');
                    row.className = task.user_id == userId ? 'bg-indigo-50' : '';
                    row.innerHTML = `
                        <td class="px-3 py-2 text-xs text-gray-900">${task.title}</td>
                        <td class="px-3 py-2 text-xs text-gray-900">${task.creator_name}</td>
                        <td class="px-3 py-2 text-xs text-gray-900">${task.deadline}</td>
                        <td class="px-3 py-2 text-xs">
                            <span class="px-1.5 py-0.5 inline-flex text-xs leading-4 font-semibold rounded-full 
                                ${task.priority === 'High' ? 'bg-red-500 text-white' : 
                                  task.priority === 'Medium' ? 'bg-amber-500 text-white' : 
                                  'bg-green-500 text-white'}">
                                ${task.priority}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs">
                            <span class="px-1.5 py-0.5 inline-flex text-xs leading-4 font-semibold rounded-full 
                                ${task.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                  'bg-green-100 text-green-800'}">
                                ${task.status}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs font-medium">
                            <a href="#" class="text-teal-600 hover:text-teal-800 toggle-description" data-id="${task.id}">
                                <svg class="h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </a>
                            <a href="#" class="text-red-600 hover:text-red-800 ml-2 delete-task" data-id="${task.id}">
                                <svg class="h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                            ${task.user_id == userId ? `
                                <a href="#" class="text-indigo-600 hover:text-indigo-800 edit-task ml-2" data-id="${task.id}">
                                    <svg class="h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15.828l-5.657 1.414 1.414-5.657L18.586 2.586z" />
                                    </svg>
                                </a>
                            ` : ''}
                        </td>
                    `;
                    taskList.appendChild(row);

                    const descriptionRow = document.createElement('tr');
                    descriptionRow.id = `description-${task.id}`;
                    descriptionRow.classList.add('hidden');
                    descriptionRow.innerHTML = `
                        <td colspan="6" class="px-3 py-2">
                            <div class="description-section">
                                <h4 class="text-xs font-medium text-gray-700 mb-1">Description</h4>
                                <div id="description-content-${task.id}" class="space-y-1 mb-2">
                                    ${task.description ? `
                                        <p class="text-xs text-gray-800">${task.description}</p>
                                    ` : `
                                        <p class="text-xs text-gray-500">No description yet.</p>
                                    `}
                                </div>
                                ${task.user_id == userId ? `
                                    <form class="description-form" data-task-id="${task.id}">
                                        <textarea class="w-full border-gray-200 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs py-1 px-2" rows="2" placeholder="Add or update description...">${task.description || ''}</textarea>
                                        <button type="submit" class="mt-1 bg-indigo-600 text-white px-2 py-1 rounded-md hover:bg-indigo-700 transition-colors text-xs">Save</button>
                                    </form>
                                    <div class="description-message text-xs mt-1"></div>
                                ` : `
                                    <p class="text-xs text-gray-500">Editing disabled for others' tasks.</p>
                                `}
                            </div>
                        </td>
                    `;
                    taskList.appendChild(descriptionRow);
                });

                reminderBanner.classList.add('hidden');
                if (data.reminders.length > 0) {
                    reminderBanner.innerHTML = `
                        <p><strong>Reminder:</strong> ${data.reminders.length} task(s) due soon: 
                        ${data.reminders.map(r => `${r.title} (Due: ${r.deadline})`).join(', ')}</p>
                    `;
                    reminderBanner.classList.remove('hidden');
                }
            } else {
                formMessage.textContent = data.error || 'Failed to fetch tasks';
                formMessage.classList.add('text-red-600');
            }
        }

        taskForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const taskId = document.getElementById('task-id').value;
            const title = document.getElementById('title').value.trim();
            const deadline = document.getElementById('deadline').value;
            const priority = document.getElementById('priority').value;
            const status = document.getElementById('status').value || 'Pending';
            
            const data = { title, deadline, priority };
            if (taskId) data.task_id = taskId;
            if (taskId) data.status = status;
            
            const response = await fetch('tasks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (response.ok) {
                formMessage.textContent = result.message;
                formMessage.classList.remove('text-red-600');
                formMessage.classList.add('text-teal-600');
                resetForm();
                fetchTasks();
            } else {
                formMessage.textContent = result.error || 'Operation failed';
                formMessage.classList.add('text-red-600');
            }
        });

        cancelEdit.addEventListener('click', resetForm);

        taskList.addEventListener('click', async (e) => {
            if (e.target.closest('.edit-task')) {
                e.preventDefault();
                const taskId = e.target.closest('.edit-task').dataset.id;
                const response = await fetch(`tasks.php?${new URLSearchParams({ status: '', priority: '', deadline: '' })}`);
                const data = await response.json();
                
                if (response.ok) {
                    const task = data.tasks.find(t => t.id == taskId);
                    if (task) {
                        document.getElementById('task-id').value = task.id;
                        document.getElementById('title').value = task.title;
                        document.getElementById('deadline').value = task.deadline;
                        document.getElementById('priority').value = task.priority;
                        document.getElementById('status').value = task.status;
                        submitTask.textContent = 'Update Task';
                        cancelEdit.classList.remove('hidden');
                        statusField.classList.remove('hidden');
                        formMessage.textContent = '';
                        window.scrollTo({ top: taskForm.offsetTop, behavior: 'smooth' });
                    }
                }
            }
        });

        taskList.addEventListener('click', async (e) => {
            if (e.target.closest('.delete-task')) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this task?')) return;
                
                const taskId = e.target.closest('.delete-task').dataset.id;
                const response = await fetch('tasks.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `task_id=${taskId}`
                });
                
                const result = await response.json();
                if (response.ok) {
                    formMessage.textContent = result.message;
                    formMessage.classList.remove('text-red-600');
                    formMessage.classList.add('text-teal-600');
                    fetchTasks();
                } else {
                    formMessage.textContent = result.error || 'Deletion failed';
                    formMessage.classList.add('text-red-600');
                }
            }
        });

        taskList.addEventListener('click', async (e) => {
            if (e.target.closest('.toggle-description')) {
                e.preventDefault();
                const taskId = e.target.closest('.toggle-description').dataset.id;
                const descriptionRow = document.getElementById(`description-${taskId}`);
                const descriptionSection = descriptionRow.querySelector('.description-section');
                descriptionRow.classList.toggle('hidden');
                descriptionSection.classList.toggle('open');
                
                if (!descriptionRow.classList.contains('hidden')) {
                    await refreshDescription(taskId);
                }
            }
        });

        async function refreshDescription(taskId) {
            const response = await fetch(`tasks.php?${new URLSearchParams({ status: '', priority: '', deadline: '' })}`);
            const data = await response.json();
            const descriptionContent = document.getElementById(`description-content-${taskId}`);
            
            if (response.ok) {
                const task = data.tasks.find(t => t.id == taskId);
                descriptionContent.innerHTML = task.description ? `
                    <p class="text-xs text-gray-800">${task.description}</p>
                ` : `
                    <p class="text-xs text-gray-500">No description yet.</p>
                `;
            } else {
                descriptionContent.innerHTML = `<p class="text-xs text-red-600">${data.error || 'Failed to load description'}</p>`;
            }
        }

        taskList.addEventListener('submit', async (e) => {
            if (e.target.classList.contains('description-form')) {
                e.preventDefault();
                const taskId = e.target.dataset.taskId;
                const description = e.target.querySelector('textarea').value.trim();
                const descriptionMessage = e.target.querySelector('.description-message');
                
                const response = await fetch('tasks.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ task_id: taskId, description })
                });
                
                const result = await response.json();
                if (response.ok) {
                    descriptionMessage.textContent = result.message;
                    descriptionMessage.classList.remove('text-red-600');
                    descriptionMessage.classList.add('text-teal-600');
                    await refreshDescription(taskId);
                } else {
                    descriptionMessage.textContent = result.error || 'Failed to save description';
                    descriptionMessage.classList.add('text-red-600');
                }
            }
        });

        // User Management
        const userList = document.getElementById('user-list');
        userList.addEventListener('click', async (e) => {
            if (e.target.closest('.delete-user')) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this user?')) return;
                
                const userId = e.target.closest('.delete-user').dataset.id;
                const response = await fetch('users.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${userId}`
                });
                
                const result = await response.json();
                if (response.ok) {
                    formMessage.textContent = result.message;
                    formMessage.classList.remove('text-red-600');
                    formMessage.classList.add('text-teal-600');
                    location.reload(); // Refresh to update user list
                } else {
                    formMessage.textContent = result.error || 'User deletion failed';
                    formMessage.classList.add('text-red-600');
                }
            }
        });

        [filterStatus, filterPriority, filterDeadline, sortBy, sortOrder].forEach(filter => {
            filter.addEventListener('change', fetchTasks);
        });

        fetchTasks();
    </script>
</body>
</html>
