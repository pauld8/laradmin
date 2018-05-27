import MixinsLoader from '../../Helpers/MixinsLoader';
import FormatDate from '../../Mixins/FormatDate';
import LaHttp from '../../Forms/LaHttp';
import {LaTable, LaTableColumn} from '../Table';
import deleteMixin from '../../Mixins/Delete';
import ServerError from '../../Mixins/ServerError';
import SimpleRouter from '../../Mixins/SimpleRouter';

export default {
    mixins: MixinsLoader.load(
        'crudBrowse',
        [FormatDate, deleteMixin, ServerError, SimpleRouter]
    ),

    props: ['typeName', 'typeSlug', 'filterableFields'],

    data() {
        return {
            loading: true,
            checkedRows: [],
            selected: {},
            search: "",
            searchClock: null,
            filtersData: {},
            query: {
                order_by: null,
                order: null,
                filters: {},
                page: 1,
                search: ""
            },
            data: {
                data: []
            }
        }
    },

    mounted() {
        this.usePushState();
    },

    created() {
        for(let key of this.filterableFields) {
            this.$set(this.query.filters, key, null);
            this.$set(this.filtersData, key, {
                data: {},
                loaded: false,
                loading: false
            });
        }
    },

    watch: {

    },

    methods: {

        fetchData() {
            this.loading = true;

            LaHttp.get(`/${this.typeSlug}/query`, this.query)
                .then(res => {
                    this.data = res.data.data;
                    this.loading = false;
                })
                .catch(err => {
                    this.alertServerError(err);

                    this.login = false;
                });

        },

        onPageChange(page) {
            this.query.page = page;

            this.pushState(this.query);
        },

        onSort(key, order) {
            this.query.order_by = key;
            this.query.order = order;

            this.pushState(this.query);
        },

        onSearch() {
            if(this.searchClock) {
                return;
            }

            this.searchClock = setTimeout(function() {
                this.query.search = this.search;
                this.pushState(this.query);
                this.searchClock = null;
            }.bind(this), 300);
        },

        onFilter() {
            this.$set(this.query, 'page', 1);
            this.pushState(this.query);
        },

        onExport() {
            this.$dialog.alert('SOON!');
        },

        onImport() {
            this.$dialog.alert('SOON!');
        },

        afterDelete(res, typeName, many = false) {
            if(many) {
                this.checkedRows = [];
            }

            this.$toast.open(
                (many && res.data.deleted ? res.data.deleted + ' ' : '') +
                `${typeName} deleted!`
            );

            this.pushState(this.query);
        },

        fetchFilterData(fieldKey) {
            if(this.filtersData[fieldKey]['loaded']) {
                return;
            }

            this.$set(this.filtersData[fieldKey], 'loading', true);

            LaHttp.get(`relationship-all/${this.typeSlug}/${fieldKey}`)
                .then(({data}) => {
                    this.$set(this.filtersData[fieldKey], 'data', data.data);
                    this.$set(this.filtersData[fieldKey], 'loading', false);
                    this.$set(this.filtersData[fieldKey], 'loaded', true);
                })
                .catch(err => {
                    this.alertServerError(err);
                    this.$set(this.filtersData[fieldKey], 'loading', false);
                });
        }

    },

    components: {
        LaTable,
        LaTableColumn
    }

}