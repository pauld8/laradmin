@extends('laradmin::layouts.page', ['bodyClass' => 'menus-browse', 'pageTitle' => trans('laradmin::menus.browse.page_title')])

@section('content')

    <browse-menus :menus='{{ json_encode($menus, JSON_UNESCAPED_UNICODE) }}' inline-template>

        <b-table :data="menus"
                 bordered
                 striped
                 checkable
                 :loading="loading"
                 mobile-cards
                 paginated
                 :per-page="25"
                 default-sort="id"
                 :selected.sync="selected"
                 :checked-rows.sync="checkedRows">

            <template scope="props">
                <b-table-column field="id" label="ID" width="40" sortable numeric>
                    @{{ props.row.id }}
                </b-table-column>

                <b-table-column field="name" label="Name" sortable>
                    @{{ props.row.name }}
                </b-table-column>

                <b-table-column field="location" label="Location" sortable>
                    @{{ props.row.location }}
                </b-table-column>

                <b-table-column field="updated_at" label="Updated" sortable>
                    <span v-html="formatDate(props.row.updated_at)"></span>
                </b-table-column>

                <b-table-column field="created_at" label="Created" sortable>
                    <span v-html="formatDate(props.row.created_at)"></span>
                </b-table-column>
            </template>

        </b-table>

    </browse-menus>

@endsection