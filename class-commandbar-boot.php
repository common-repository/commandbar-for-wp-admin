<?php

class CommandBar_Boot
{
    public function __construct()
    {
        if (COMMANDBAR_ENABLE_FOR_NON_ADMIN_USERS) {
            add_action('wp_head', [$this, 'snippet'], 0);
            add_action('wp_head', [$this, 'boot'], 0);
        }

        if(COMMANDBAR_ENABLE_FOR_ADMIN_USERS){
            add_action('admin_enqueue_scripts', [$this, 'snippet'], 0);
            add_action('admin_enqueue_scripts', [$this, 'boot'], 0);
        }
    }

    function snippet()
    {
        $ORG_ID = get_option('commandbar_plugin_org_id');
        if (!$ORG_ID) {
            return;
        }
?>
        <script>
            (function() {
                var o = <?php echo json_encode($ORG_ID) ?>,
                    n = ["Object.assign", "Symbol", "Symbol.for"].join("%2C"),
                    a = window;

                function r(o, n) {
                    void 0 === n && (n = !1), "complete" !== document.readyState && window.addEventListener("load", r.bind(null, o, n), {
                        capture: !1,
                        once: !0
                    });
                    var a = document.createElement("script");
                    a.type = "text/javascript", a.async = n, a.src = o, document.head.appendChild(a)
                }

                function t() {
                    var n;
                    if (void 0 === a.CommandBar) {
                        delete a.__CommandBarBootstrap__;
                        var t = Symbol.for("CommandBar::configuration"),
                            e = Symbol.for("CommandBar::orgConfig"),
                            i = Symbol.for("CommandBar::disposed"),
                            m = Symbol.for("CommandBar::isProxy"),
                            l = Symbol.for("CommandBar::queue"),
                            c = Symbol.for("CommandBar::unwrap"),
                            d = [],
                            s = localStorage.getItem("commandbar.lc"),
                            u = s && s.includes("local") ? "http://localhost:8000" : "https://api.commandbar.com",
                            f = Object.assign(((n = {})[t] = {
                                uuid: o
                            }, n[e] = {}, n[i] = !1, n[m] = !0, n[l] = new Array, n[c] = function() {
                                return f
                            }, n), a.CommandBar),
                            p = ["addCommand", "boot", "getShortcuts"],
                            y = f;
                        Object.assign(f, {
                            shareCallbacks: function() {
                                return {}
                            },
                            shareContext: function() {
                                return {}
                            }
                        }), a.CommandBar = new Proxy(f, {
                            get: function(o, n) {
                                return n in y ? f[n] : p.includes(n) ? function() {
                                    var o = Array.prototype.slice.call(arguments);
                                    return new Promise((function(a, r) {
                                        o.unshift(n, a, r), f[l].push(o)
                                    }))
                                } : function() {
                                    var o = Array.prototype.slice.call(arguments);
                                    o.unshift(n), f[l].push(o)
                                }
                            }
                        }), null !== s && d.push("lc=" + s), d.push("version=2"), r(u + "/latest/" + o + "?" + d.join("&"), !0)
                    }
                }
                void 0 === Object.assign || "undefined" == typeof Symbol || void 0 === Symbol.for ? (a.__CommandBarBootstrap__ = t, r("https://polyfill.io/v3/polyfill.min.js?version=3.101.0&callback=__CommandBarBootstrap__&features=" + n)) : t();
            })();
        </script>
    <?php
    }

    function boot()
    {
        $current_user_id = wp_get_current_user()->id;
        $end_user_secret = get_option('commandbar_plugin_end_user_secret');

        $end_user_hmac = $current_user_id > 0 ? hash_hmac('sha256', strval($current_user_id), $end_user_secret) : NULL;

    ?>
        <script>
            window.CommandBar.boot(
                <?php echo json_encode($current_user_id > 0 ? strval($current_user_id) : NULL) ?>,
                {},
                <?php echo json_encode([
                    'canOpenEditor' => !!COMMANDBAR_ENABLE_EDITOR_ACCESS,
                    'hmac' => $end_user_hmac, 'formFactor' => ['type' => 'modal']
                ]) ?>,
                <?php echo json_encode([
                    'wp_plugin_version' => COMMANDBAR_PLUGIN_VERSION
                ]) ?>
            );
        </script>
    <?php
    }
}


new CommandBar_Boot();
