if (typeof Craft.OsimTenon === typeof undefined) {
    Craft.OsimTenon = {}
}

Craft.OsimTenon.PageIndex = Craft.BaseElementIndex.extend({
    init: function (elementType, $container, settings) {
        this.on('selectSource', this.updateUrl.bind(this));
        this.on('selectSite', this.updateUrl.bind(this));
        this.base(elementType, $container, settings);
    },

    getDefaultSourceKey: function() {
        if (this.settings.context === 'index') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);
                const projectId = ($source.data('projectid') || '').toString()

                if (projectId == osimTenonProjectId) {
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
            let url = 'osim-tenon/pages';

            const projectId = this.$source.data('projectid')

            if (projectId) {
                url += '/projects/' + projectId
            }

            Craft.setPath(url)
        }
    }
})

Craft.registerElementIndexClass(
    'osim\\craft\\tenon\\elements\\Page',
    Craft.OsimTenon.PageIndex
)
