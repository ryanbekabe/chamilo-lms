<template>
  <div class="documents-list">
    <Toolbar
            :handle-add="addHandler"
            :handle-add-document="addDocumentHandler"
    />

    <v-container grid-list-xl fluid>
      <v-layout row wrap>
        <v-flex lg12>
          <DataFilter :handle-filter="onSendFilter" :handle-reset="resetFilter">
            <DocumentsFilterForm
              ref="filterForm"
              :values="filters"
              slot="filter"
            />
          </DataFilter>
          <br />
          <v-data-table
            v-model="selected"
            :headers="headers"
            :items="items"
            :items-per-page.sync="options.itemsPerPage"
            :loading="isLoading"
            :loading-text="$t('Loading...')"
            :options.sync="options"
            :server-items-length="totalItems"
            class="elevation-1"
            item-key="@id"
            show-select
            @update:options="onUpdateOptions"
          >
            <template slot="item.resourceNode.title" slot-scope="{ item }">
              <div v-if="item['resourceNode']['resourceFile']">
<!--                <a @click="showHandler(item)" >-->
<!--                  {{ item['contentUrl'] }}-->
<!--                </a>-->
                <a data-fancybox="gallery"  :href=" item['contentUrl'] " >
                    {{ item['resourceNode']['title'] }}
                </a>
              </div>
              <div v-else>
                <a @click="handleClick(item)">
                  {{ item['resourceNode']['title'] }}
                </a>
              </div>
            </template>

<!--            <template slot="item.resourceNode" slot-scope="{ item }">-->
<!--              {{ item['@id'] }}-->
<!--            </template>-->

            <template slot="item.resourceNode.updatedAt" slot-scope="{ item }">
              {{ item.resourceNode.updatedAt | moment("from", "now") }}
            </template>

            <ActionCell
              slot="item.action"
              slot-scope="props"
              :handle-show="() => showHandler(props.item)"
              :handle-edit="() => editHandler(props.item)"
              :handle-delete="() => deleteHandler(props.item)"
            ></ActionCell>
          </v-data-table>
        </v-flex>
      </v-layout>
    </v-container>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell';
import DocumentsFilterForm from '../../components/documents/Filter';
import DataFilter from '../../components/DataFilter';
import Toolbar from '../../components/Toolbar';

export default {
  name: 'DocumentsList',
  servicePrefix: 'Documents',
  mixins: [ListMixin],
  components: {
    Toolbar,
    ActionCell,
    DocumentsFilterForm,
    DataFilter
  },
  data() {
    return {
      headers: [
        {text: 'Title', value: 'resourceNode.title', sortable: true},
        {text: 'Modified', value: 'resourceNode.updatedAt', sortable: true},
        {text: 'Size', value: 'resourceNode.resourceFile.size', sortable: true},
        {text: 'Actions', value: 'action', sortable: false}
      ],
      selected: []
    };
  },
  computed: {
    ...mapGetters('documents', {
      items: 'list'
    }),
    ...mapFields('documents', {
      deletedItem: 'deleted',
      error: 'error',
      isLoading: 'isLoading',
      resetList: 'resetList',
      totalItems: 'totalItems',
      view: 'view'
    })
  },
  methods: {
    ...mapActions('documents', {
      getPage: 'fetchAll',
      deleteItem: 'del'
    })
  }
};
</script>
