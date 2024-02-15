<?php
/**
 * WP Search With Algolia instantsearch template file.
 *
 * @author  WebDevStudios <contact@webdevstudios.com>
 * @since   1.0.0
 *
 * @version 2.7.1
 * @package WebDevStudios\WPSWA
 */

get_header();

?>

    <div id="ais-wrapper">
        <main id="ais-main">
            <div class="algolia-search-box-wrapper">
                <div id="algolia-search-box"></div>
                <svg class="search-icon" width="25" height="25" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"><path d="M24.828 31.657a16.76 16.76 0 0 1-7.992 2.015C7.538 33.672 0 26.134 0 16.836 0 7.538 7.538 0 16.836 0c9.298 0 16.836 7.538 16.836 16.836 0 3.22-.905 6.23-2.475 8.79.288.18.56.395.81.645l5.985 5.986A4.54 4.54 0 0 1 38 38.673a4.535 4.535 0 0 1-6.417-.007l-5.986-5.986a4.545 4.545 0 0 1-.77-1.023zm-7.992-4.046c5.95 0 10.775-4.823 10.775-10.774 0-5.95-4.823-10.775-10.774-10.775-5.95 0-10.775 4.825-10.775 10.776 0 5.95 4.825 10.775 10.776 10.775z" fill-rule="evenodd"></path></svg>
                <div id="algolia-stats"></div>
                <div id="algolia-powered-by"></div>
            </div>
            <div id="algolia-hits"></div>
            <div id="algolia-pagination"></div>
        </main>
        <aside id="ais-facets">

            <div>
                <h3 class="widgettitle"><?php esc_html_e( 'Catégories', 'wp-search-with-algolia' ); ?></h3>
                <section class="ais-facets" id="facet-categories"></section>
            </div>
            
        </aside>
    </div>

    <script type="text/html" id="tmpl-instantsearch-hit">
        <article itemtype="http://schema.org/Article">
            <# if ( data.images.thumbnail ) { #>
                <div class="ais-hits--thumbnail">
                    <a href="{{ data.permalink }}" title="{{ data.post_title }}" class="ais-hits--thumbnail-link">
                        <img src="{{ data.images.thumbnail.url }}" alt="{{ data.post_title }}" title="{{ data.post_title }}" itemprop="image" />
                    </a>
                </div>
            <# } #>

            <div class="ais-hits--content">
                <h2 itemprop="name headline"><a href="{{ data.permalink }}" title="{{ data.post_title }}" class="ais-hits--title-link" itemprop="url">{{{ data._highlightResult.post_title.value }}}</a></h2>
                <div class="excerpt">
                    <p>
                        <# if ( data._snippetResult['content'] ) { #>
                            <span class="suggestion-post-content ais-hits--content-snippet">{{{ data._snippetResult['content'].value }}}</span>
                        <# } #>
                    </p>
                </div>
                <?php
                do_action( 'algolia_instantsearch_after_hit' );
                ?>
            </div>
            <div class="ais-clearfix"></div>
        </article>
    </script>


    <script type="text/javascript">
        window.addEventListener('load', function() {
            if ( document.getElementById("algolia-search-box") ) {
                if ( algolia.indices.searchable_posts === undefined && document.getElementsByClassName("admin-bar").length > 0) {
                    alert('It looks like you haven\'t indexed the searchable posts index. Please head to the Indexing page of the Algolia Search plugin and index it.');
                }

                /* Instantiate instantsearch.js */
                var search = instantsearch({
                    indexName: algolia.indices.searchable_posts.name,
                    searchClient: algoliasearch( algolia.application_id, algolia.search_api_key ),
                    routing: {
                        router: instantsearch.routers.history({ writeDelay: 1000 }),
                        stateMapping: {
                            stateToRoute( indexUiState ) {
                                return {
                                    s: indexUiState[ algolia.indices.searchable_posts.name ].query,
                                    page: indexUiState[ algolia.indices.searchable_posts.name ].page
                                }
                            },
                            routeToState( routeState ) {
                                const indexUiState = {};
                                indexUiState[ algolia.indices.searchable_posts.name ] = {
                                    query: routeState.s,
                                    page: routeState.page
                                };
                                return indexUiState;
                            }
                        }
                    }
                });

                search.addWidgets([

                    /* Search box widget */
                    instantsearch.widgets.searchBox({
                        container: '#algolia-search-box',
                        placeholder: 'Search for...',
                        showReset: false,
                        showSubmit: false,
                        showLoadingIndicator: false,
                    }),

                    /* Stats widget */
                    instantsearch.widgets.stats({
                        container: '#algolia-stats'
                    }),

                    instantsearch.widgets.configure({
                        hitsPerPage: 10,
                    }),

                    /* Hits widget */
                    instantsearch.widgets.hits({
                        container: '#algolia-hits',
                        templates: {
                            empty: 'No results were found for "<strong>{{query}}</strong>".',
                            item: wp.template('instantsearch-hit')
                        }
                    }),

                    /* Pagination widget */
                    instantsearch.widgets.pagination({
                        container: '#algolia-pagination'
                    }),

                    /* Categories refinement widget */
                    instantsearch.widgets.hierarchicalMenu({
                        container: '#facet-categories',
                        separator: ' > ',
                        sortBy: ['count'],
                        attributes: ['taxonomies_hierarchical.category.lvl0', 'taxonomies_hierarchical.category.lvl1', 'taxonomies_hierarchical.category.lvl2'],
                    })

                    /* Additional widgets can be added here */
                ]);

                if ( algolia.powered_by_enabled ) {
                    search.addWidget(
                        /* Search powered-by widget */
                        instantsearch.widgets.poweredBy({
                            container: '#algolia-powered-by'
                        })
                    );
                }

                /* Start */
                search.start();

                document.querySelector("#algolia-search-box input[type='search']").select();
            }
        });
    </script>

<?php

get_footer();