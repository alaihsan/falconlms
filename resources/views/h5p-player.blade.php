<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $title }}</title>
        <style>
            html,
            body {
                margin: 0;
                background: #ffffff;
                font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            }

            #h5p-status {
                padding: 24px;
                color: #475569;
                font-size: 14px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div id="h5p-container"></div>
        <p id="h5p-status" role="status">Memuat konten interaktif...</p>

        <script>
            /*
             * The sandbox blocks browser storage and cookies. H5P only uses them as a convenience,
             * so in-memory stand-ins keep it running without exposing anything from the application.
             */
            (function () {
                function memoryStorage() {
                    var store = {};

                    return {
                        getItem: function (key) {
                            return Object.prototype.hasOwnProperty.call(store, key) ? store[key] : null;
                        },
                        setItem: function (key, value) {
                            store[key] = String(value);
                        },
                        removeItem: function (key) {
                            delete store[key];
                        },
                        clear: function () {
                            store = {};
                        },
                        key: function (index) {
                            return Object.keys(store)[index] || null;
                        },
                        get length() {
                            return Object.keys(store).length;
                        },
                    };
                }

                ['localStorage', 'sessionStorage'].forEach(function (name) {
                    try {
                        void window[name];
                    } catch (error) {
                        Object.defineProperty(window, name, { configurable: true, value: memoryStorage() });
                    }
                });

                try {
                    void document.cookie;
                } catch (error) {
                    Object.defineProperty(document, 'cookie', {
                        configurable: true,
                        get: function () {
                            return '';
                        },
                        set: function () {},
                    });
                }
            })();
        </script>
        <script src="{{ $playerScript }}"></script>
        <script>
            (function () {
                var status = document.getElementById('h5p-status');
                var options = @json($options);

                if (!window.H5PStandalone) {
                    status.textContent = 'Pemutar H5P gagal dimuat.';
                    return;
                }

                new window.H5PStandalone.H5P(document.getElementById('h5p-container'), options)
                    .then(function () {
                        status.remove();
                    })
                    .catch(function (error) {
                        console.error(error && error.message ? error.message : error);
                        status.textContent = 'Konten H5P ini tidak dapat ditampilkan. Periksa kembali paket yang diunggah.';
                    });
            })();
        </script>
    </body>
</html>
