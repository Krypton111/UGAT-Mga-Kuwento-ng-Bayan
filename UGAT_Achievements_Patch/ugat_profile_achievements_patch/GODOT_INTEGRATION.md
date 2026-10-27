# Godot integration: send unlocked achievements to the signed-in UGAT account

The website patch adds `save_game_achievement.php`. It associates submitted IDs with the user represented by the PHP login session, so **do not send a username or user ID from Godot**.

The five IDs expected by the website are:

- `heritage_collector`
- `ingredients_in_pot`
- `collect_eggs`
- `ending_city`
- `ending_stay`

## Add a sync helper to the Godot achievement manager

This is for the **web export** hosted from the same site as `dashboard.php` under `Assets/games/lair/`. Call `_sync_achievements_to_website()` whenever a new achievement is unlocked, after adding the ID to `GameState.achievements_unlocked`.

```gdscript
func _sync_achievements_to_website() -> void:
    if not OS.has_feature("web"):
        return
    var stored_ids: Variant = GameState.get("achievements_unlocked")
    if not stored_ids is Array:
        push_warning("GameState.achievements_unlocked is missing or is not an Array.")
        return

    var ids: Array = stored_ids
    if ids.is_empty():
        return

    # The game export is under /Assets/games/lair/, so three parent levels
    # resolve the endpoint at the website root without hardcoding localhost.
    var endpoint: String = JavaScriptBridge.eval(
        "new URL('../../../save_game_achievement.php', window.location.href).href",
        true
    )
    if endpoint.is_empty():
        push_warning("Could not resolve the UGAT achievement endpoint.")
        return

    var request_node := HTTPRequest.new()
    get_tree().root.add_child(request_node)
    request_node.request_completed.connect(
        func(result: int, response_code: int, _headers: PackedStringArray, body: PackedByteArray) -> void:
            if result != HTTPRequest.RESULT_SUCCESS or response_code < 200 or response_code >= 300:
                push_warning("Achievement sync failed (HTTP %s): %s" % [
                    response_code,
                    body.get_string_from_utf8()
                ])
            request_node.queue_free()
    )

    var headers := PackedStringArray(["Content-Type: application/json"])
    var body := JSON.stringify({"achievement_ids": ids})
    var error := request_node.request(
        endpoint,
        headers,
        HTTPClient.METHOD_POST,
        body
    )
    if error != OK:
        request_node.queue_free()
        push_warning("Could not start achievement sync request: %s" % error)
```

If your achievement manager uses a different variable name or stores achievement IDs somewhere other than `GameState.achievements_unlocked`, adapt that one line to the actual source. Ensure the stored values are the five IDs above, not the display titles.

## Test checklist

1. Apply the website patch and run the SQL file in `game_launcher`.
2. Log in to UGAT, launch the game from the dashboard, and unlock one achievement.
3. Return to Profile and verify the achievement appears with its unlock time.
4. Log out and sign in with a different account. The first account's achievements must not appear.
5. Log back into the first account and confirm the achievement remains.

The endpoint requires an authenticated PHP session. The game must be served from the same origin as UGAT for the browser session cookie to be sent. A locally opened `file://` export will not work with this session-based design.
