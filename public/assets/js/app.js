$(function () {
    "use strict";

    /* ==========================================================================
       LÓGICA SMART LOADER (Definitiva)
       ========================================================================== */

    // 1. Limpieza inicial: Asegurar que el loader esté oculto al cargar el JS
    $(".pace").removeClass("pace-active");

    // Variable para controlar el temporizador
    let loaderTimeout;

    // 2. Interceptar clicks SOLO en enlaces de navegación real
    // Excluimos links vacíos, anclas (#), javascript:; y links que abren en otra pestaña
    $(document).on("click", "a", function (e) {
        const url = $(this).attr("href");
        const target = $(this).attr("target");
        const isDownload =
            $(this).prop("download") || $(this).attr("download") !== undefined;

        // FILTRO MEJORADO:
        // No activar loader si es descarga, si el link es a assets, o si es un link vacío
        if (
            !url ||
            url === "#" ||
            isDownload || // Ignora si tiene atributo download
            url.includes("assets/") || // Ignora si es un recurso estático
            url.startsWith("javascript") ||
            url.startsWith("mailto") ||
            url.startsWith("tel") ||
            target === "_blank"
        ) {
            return;
        }

        if (loaderTimeout) clearTimeout(loaderTimeout);

        loaderTimeout = setTimeout(function () {
            $(".pace").addClass("pace-active");
        }, 2000);
    });

    // 4. SEGURIDAD: Si el usuario usa el botón "Atrás" del navegador (BFCache)
    // Este evento se dispara siempre que la página se muestra, incluso desde caché.
    $(window).on("pageshow", function (event) {
        if (loaderTimeout) clearTimeout(loaderTimeout);
        $(".pace").removeClass("pace-active");
    });

    /* ==========================================================================
       FIN LÓGICA LOADER
       ========================================================================== */

    ($(".mobile-search-icon").on("click", function () {
        $(".search-bar").addClass("full-search-bar");
    }),
        $(".search-close").on("click", function () {
            $(".search-bar").removeClass("full-search-bar");
        }),
        $(".mobile-toggle-menu").on("click", function () {
            $(".wrapper").addClass("toggled");
        }),
        $(".toggle-icon").click(function () {
            $(".wrapper").hasClass("toggled")
                ? ($(".wrapper").removeClass("toggled"),
                  $(".sidebar-wrapper").unbind("hover"))
                : ($(".wrapper").addClass("toggled"),
                  $(".sidebar-wrapper").hover(
                      function () {
                          $(".wrapper").addClass("sidebar-hovered");
                      },
                      function () {
                          $(".wrapper").removeClass("sidebar-hovered");
                      },
                  ));
        }),
        $(document).ready(function () {
            const currentUrl = window.location.href;

            // ✅ RUTAS DONDE SE DEBE OCULTAR LA BARRA LATERAL
            const hideRoutes = [
                "sales",
                "quotes",
                "sales_interface",
                "tables_view",
                "sales_restaurant",
                "sales_restaurant/create",
                "sales_restaurant/edit",
                "sales_restaurant/pay",
            ];

            // ✅ VERIFICAR SI LA URL ACTUAL COINCIDE CON ALGUNA RUTA
            const shouldHide = hideRoutes.some(
                (route) =>
                    currentUrl.includes(route) || currentUrl.endsWith(route),
            );

            if (shouldHide) {
                if ($(window).width() < 1025) {
                    // ✅ MÓVIL: SOLO QUITAR CLASES
                    $(".wrapper").removeClass("toggled sidebar-hovered");
                } else {
                    // ✅ ESCRITORIO: OCULTAR BARRA LATERAL CON HOVER
                    $(".wrapper").hasClass("toggled")
                        ? ($(".wrapper").removeClass("toggled"),
                          $(".sidebar-wrapper").unbind("hover"))
                        : ($(".wrapper").addClass("toggled"),
                          $(".sidebar-wrapper").hover(
                              function () {
                                  $(".wrapper").addClass("sidebar-hovered");
                              },
                              function () {
                                  $(".wrapper").removeClass("sidebar-hovered");
                              },
                          ));
                }
            }
        }));

    ($(document).ready(function () {
        ($(window).on("scroll", function () {
            $(this).scrollTop() > 300
                ? $(".back-to-top").fadeIn()
                : $(".back-to-top").fadeOut();
        }),
            $(".back-to-top").on("click", function () {
                return (
                    $("html, body").animate(
                        {
                            scrollTop: 0,
                        },
                        600,
                    ),
                    !1
                );
            }));
    }),
        $(function () {
            for (
                var e = window.location,
                    o = $(".metismenu li a")
                        .filter(function () {
                            return this.href == e;
                        })
                        .addClass("")
                        .parent()
                        .addClass("mm-active");
                o.is("li");
            )
                o = o
                    .parent("")
                    .addClass("mm-show")
                    .parent("")
                    .addClass("mm-active");
        }),
        $(function () {
            $("#menu").metisMenu();
        }),
        $(".chat-toggle-btn").on("click", function () {
            $(".chat-wrapper").toggleClass("chat-toggled");
        }),
        $(".chat-toggle-btn-mobile").on("click", function () {
            $(".chat-wrapper").removeClass("chat-toggled");
        }),
        $(".email-toggle-btn").on("click", function () {
            $(".email-wrapper").toggleClass("email-toggled");
        }),
        $(".email-toggle-btn-mobile").on("click", function () {
            $(".email-wrapper").removeClass("email-toggled");
        }),
        $(".compose-mail-btn").on("click", function () {
            $(".compose-mail-popup").show();
        }),
        $(".compose-mail-close").on("click", function () {
            $(".compose-mail-popup").hide();
        }),
        $(".switcher-btn").on("click", function () {
            $(".switcher-wrapper").toggleClass("switcher-toggled");
        }),
        $(".close-switcher").on("click", function () {
            $(".switcher-wrapper").removeClass("switcher-toggled");
        }),
        $("#lightmode").on("click", function () {
            $("html").attr("class", "light-theme");
        }),
        $("#darkmode").on("click", function () {
            $("html").attr("class", "dark-theme");
        }),
        $("#semidark").on("click", function () {
            $("html").attr("class", "semi-dark");
        }),
        $("#minimaltheme").on("click", function () {
            $("html").attr("class", "minimal-theme");
        }),
        $("#headercolor1").on("click", function () {
            ($("html").addClass("color-header headercolor1"),
                $("html").removeClass(
                    "headercolor2 headercolor3 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8",
                ));
        }),
        $("#headercolor2").on("click", function () {
            ($("html").addClass("color-header headercolor2"),
                $("html").removeClass(
                    "headercolor1 headercolor3 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8",
                ));
        }),
        $("#headercolor3").on("click", function () {
            ($("html").addClass("color-header headercolor3"),
                $("html").removeClass(
                    "headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8",
                ));
        }),
        $("#headercolor4").on("click", function () {
            ($("html").addClass("color-header headercolor4"),
                $("html").removeClass(
                    "headercolor1 headercolor2 headercolor3 headercolor5 headercolor6 headercolor7 headercolor8",
                ));
        }),
        $("#headercolor5").on("click", function () {
            ($("html").addClass("color-header headercolor5"),
                $("html").removeClass(
                    "headercolor1 headercolor2 headercolor4 headercolor3 headercolor6 headercolor7 headercolor8",
                ));
        }),
        $("#headercolor6").on("click", function () {
            ($("html").addClass("color-header headercolor6"),
                $("html").removeClass(
                    "headercolor1 headercolor2 headercolor4 headercolor5 headercolor3 headercolor7 headercolor8",
                ));
        }),
        $("#headercolor7").on("click", function () {
            ($("html").addClass("color-header headercolor7"),
                $("html").removeClass(
                    "headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor3 headercolor8",
                ));
        }),
        $("#headercolor8").on("click", function () {
            ($("html").addClass("color-header headercolor8"),
                $("html").removeClass(
                    "headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor7 headercolor3",
                ));
        }));

    // sidebar colors
    $("#sidebarcolor1").click(theme1);
    $("#sidebarcolor2").click(theme2);
    $("#sidebarcolor3").click(theme3);
    $("#sidebarcolor4").click(theme4);
    $("#sidebarcolor5").click(theme5);
    $("#sidebarcolor6").click(theme6);
    $("#sidebarcolor7").click(theme7);
    $("#sidebarcolor8").click(theme8);

    function theme1() {
        $("html").attr("class", "color-sidebar sidebarcolor1");
    }

    function theme2() {
        $("html").attr("class", "color-sidebar sidebarcolor2");
    }

    function theme3() {
        $("html").attr("class", "color-sidebar sidebarcolor3");
    }

    function theme4() {
        $("html").attr("class", "color-sidebar sidebarcolor4");
    }

    function theme5() {
        $("html").attr("class", "color-sidebar sidebarcolor5");
    }

    function theme6() {
        $("html").attr("class", "color-sidebar sidebarcolor6");
    }

    function theme7() {
        $("html").attr("class", "color-sidebar sidebarcolor7");
    }

    function theme8() {
        $("html").attr("class", "color-sidebar sidebarcolor8");
    }
});

document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener("hide.bs.modal", function (event) {
        if (document.activeElement) {
            document.activeElement.blur();
        }
    });
});

function validatePriceInput(input) {
    const maxIntegers = parseInt(input.dataset.maxIntegers) || 8;
    const maxDecimals = parseInt(input.dataset.maxDecimals) || 2;

    input.value = input.value
        .replace(/[^0-9.]/g, "")
        .replace(/(\..*)\./g, "$1");

    let parts = input.value.split(".");

    if (parts[0] && parts[0].length > maxIntegers) {
        parts[0] = parts[0].substring(0, maxIntegers);
    }
    if (parts[1] && parts[1].length > maxDecimals) {
        parts[1] = parts[1].substring(0, maxDecimals);
    }

    input.value = parts.join(".");
}

document.addEventListener("DOMContentLoaded", function () {
    const priceInputs = document.querySelectorAll(".price-decimal");

    priceInputs.forEach((input) => {
        input.addEventListener("input", function () {
            validatePriceInput(this);
        });
        input.addEventListener("paste", function (e) {
            setTimeout(() => validatePriceInput(this), 10);
        });
    });
});
