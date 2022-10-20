if (typeof Craft.OsimTenon === typeof undefined) {
    Craft.OsimTenon = {}
}

// Override element editor to hide sidebar and save button
Craft.OsimTenon.ElementIssueSlideout = Craft.ElementEditorSlideout.extend({
    init: function (element, settings) {
        this.base(element, settings)
        this.settings.showHeader = false
        this.$saveBtn.addClass('hidden')
        this.$container.addClass('osim-tenon-issues')

        this.on('load', () => {
            const $preview = this.$container.find('.osim-tenon-preview')
            const $frame = this.$container.find('.osim-tenon-frame')

            if (!$frame.length) {
                return
            }

            let scaleX = 100
            let scaleY = 100

            if ($frame.width() > $preview.width()) {
                scaleX = Math.floor($preview.width() / $frame.width() * 100)
            }

            if ($frame.height() > $preview.height()) {
                scaleY = Math.floor($preview.height() / $frame.height() * 100)
            }

            if (scaleX < 100 || scaleY < 100) {
                if (scaleX < scaleY) {
                    $frame.css('transform', 'translate(-50%, -50%) scale(' + scaleX + '%)')
                } else {
                    $frame.css('transform', 'translate(-50%, -50%) scale(' + scaleY + '%)')
                }
            }

            $frame.find('iframe').on('load', () => {
                $frame.addClass('osim-tenon-visible')
            })
        });
        this.on('beforeClose', () => {
            const $frame = this.$container.find('.osim-tenon-frame')
            $frame.removeClass('osim-tenon-visible')
        })
    },

    update: function(data) {
        data.sidebar = null
        data.editUrl = false

        return this.base(data)
    },

    open: function () {
        // Override static update function temporarily so that
        // it will result in a different hardcoded left position
        const original = Craft.Slideout.updateStyles

        // We want a 30% width instead of 50%
        let updateStyles = original.toString().replace('50', '70');

        eval('Craft.Slideout.updateStyles = ' + updateStyles)

        this.base()

        Craft.Slideout.updateStyles = original
    }
})

Craft.OsimTenon.IssueIndex = Craft.BaseElementIndex.extend({
    init: function (elementType, $container, settings) {
        this.on('selectSource', this.updateUrl.bind(this));
        this.on('selectSite', this.updateUrl.bind(this));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        this.base();

        this.$elements.on('click', 'tbody tr th .element', e => {
            e.preventDefault();

            // Open the element slide-out
            var $element = $(e.target).parents('tr').find('.element');

            new Craft.OsimTenon.ElementIssueSlideout(
                $element,
                {
                    elementType: $element.data('type'),
                }
            )
        });
    },

    getDefaultSourceKey: function() {
        if (this.settings.context === 'index') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);
                const pageId = ($source.data('pageid') || '').toString()
                const projectId = ($source.data('projectid') || '').toString()
                const viewportId = ($source.data('viewportid') || '').toString()

                if (pageId == osimTenonPageId &&
                    projectId == osimTenonProjectId &&
                    viewportId == osimTenonViewportId
                ) {
                    return $source.data('key')
                }
            }
        }

        return this.base()
    },

    updateUrl: function() {
        if (!this.$source) {
            return;
        }

        if (this.settings.context === 'index') {
            let url = 'osim-tenon';

            const pageId = this.$source.data('pageid')

            if (pageId) {
                url += '/pages/' + pageId + '/issues'

                const viewportId = this.$source.data('viewportid')

                if (viewportId) {
                    url += '/viewports/' + viewportId
                }
            } else {
                url += '/issues'

                const projectId = this.$source.data('projectid')

                if (projectId) {
                    url += '/projects/' + projectId

                    const viewportId = this.$source.data('viewportid')

                    if (viewportId) {
                        url += '/viewports/' + viewportId
                    }
                }
            }

            Craft.setPath(url)
        }
    }
})

Craft.registerElementIndexClass(
    'osim\\craft\\tenon\\elements\\Issue',
    Craft.OsimTenon.IssueIndex
)
