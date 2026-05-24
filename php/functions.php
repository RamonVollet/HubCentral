<?php
function data_dir() {
    return __DIR__ . '/../data';
}
function users_file() {
    return data_dir() . '/users.json';
}
function sites_file() {
    return data_dir() . '/sites.json';
}
function sessions_file() {
    return data_dir() . '/sessions.json';
}

function ensure_data_dir() {
    $d = data_dir();
    if (!is_dir($d)) mkdir($d, 0755, true);
}

function load_json($file) {
    ensure_data_dir();
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}

function save_json($file, $arr) {
    ensure_data_dir();
    file_put_contents(
        $file,
        json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function load_users() {
    return load_json(users_file());
}
function save_users($u) {
    save_json(users_file(), $u);
}

function find_user($username) {
    $users = load_users();
    foreach ($users as $user) {
        if (isset($user['username']) && $user['username'] === $username) {
            return $user;
        }
    }
    return null;
}

function username_exists($username) {
    return find_user($username) !== null;
}

function add_user($username, $password, $role = 'user', $access = []) {
    $users = load_users();

    if (username_exists($username)) return false;

    if (!is_array($access)) {
        if (empty($access)) {
            $access = [];
        } else {
            $access = [$access];
        }
    }

    $users[] = [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'access' => array_values(array_map('strtolower', array_unique($access)))
    ];

    save_users($users);
    return true;
}

function update_user_access($username, $access_array) {
    $users = load_users();
    foreach ($users as &$u) {
        if (isset($u['username']) && $u['username'] === $username) {
            if (!is_array($access_array)) $access_array = [$access_array];
            $u['access'] = array_values(array_map('strtolower', array_unique($access_array)));
            save_users($users);
            return true;
        }
    }
    return false;
}

function update_user_password($username, $new_password) {
    $users = load_users();
    foreach ($users as &$u) {
        if (isset($u['username']) && $u['username'] === $username) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);

            $u['password'] = $hash;

            save_users($users);
            return true;
        }
    }
    return false;
}


function remove_user($username) {
    $users = load_users();
    $out = [];
    foreach ($users as $u) {
        if (!isset($u['username']) || $u['username'] !== $username) {
            $out[] = $u;
        }
    }
    save_users($out);
}

function load_sites() {
    return load_json(sites_file());
}
function save_sites($s) {
    save_json(sites_file(), $s);
}

function add_site($name, $icon, $folder) {
    $sites = load_sites();
    $folder = strtolower(trim($folder));

    foreach ($sites as $site) {
        if (($site['folder'] ?? '') === $folder) {
            return false;
        }
    }

    $sites[] = [
        'name' => $name,
        'icon' => $icon,
        'folder' => $folder,
        'roles' => ['admin', 'user']
    ];
    save_sites($sites);
    return true;
}

function update_site($folder, $name, $icon, $newFolder = null) {
    $folder = strtolower(trim($folder));
    $sites = load_sites();

    foreach ($sites as &$s) {
        if ($s['folder'] === $folder) {
            $oldFolder = $folder;
            $s['name'] = $name;
            $s['icon'] = $icon;

            if ($newFolder && $newFolder !== $oldFolder) {
                $newFolder = strtolower(trim($newFolder));

                foreach ($sites as $existingSite) {
                    if (($existingSite['folder'] ?? '') === $newFolder) {
                        return false;
                    }
                }

                $oldPath = __DIR__ . "/../sites/" . $oldFolder;
                $newPath = __DIR__ . "/../sites/" . $newFolder;

                if (is_dir($oldPath)) {
                    rename($oldPath, $newPath);
                }

                $s['folder'] = $newFolder;
            }

            save_sites($sites);
            return true;
        }
    }
    return false;
}


function delete_site($folder) {
    $folder = strtolower(trim($folder));
    $sites = load_sites();

    $sites = array_filter($sites, function($s) use ($folder) {
        return $s['folder'] !== $folder;
    });
    save_sites(array_values($sites));

    $path = __DIR__ . "/../sites/" . $folder;

    if (is_dir($path)) {
        $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) rmdir($file->getRealPath());
            else unlink($file->getRealPath());
        }

        rmdir($path);
    }

    return true;
}

function load_sessions() {
    return load_json(sessions_file());
}
function save_sessions($s) {
    save_json(sessions_file(), $s);
}

function set_user_session_id($username, $session_id) {
    $sessions = load_sessions();
    $sessions[$username] = $session_id;
    save_sessions($sessions);
}

function clear_user_session_id($username) {
    $sessions = load_sessions();
    if (isset($sessions[$username])) {
        unset($sessions[$username]);
        save_sessions($sessions);
    }
}

function validate_user_session($username, $session_id) {
    $sessions = load_sessions();
    if (!isset($sessions[$username])) return false;
    return $sessions[$username] === $session_id;
}

function initialize_defaults() {
    ensure_data_dir();
    if (!file_exists(users_file())) save_users([]);
    if (!file_exists(sites_file())) save_sites([]);
    if (!file_exists(sessions_file())) save_sessions([]);
}
initialize_defaults();

?>
