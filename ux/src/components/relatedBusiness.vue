<template>
  <div class="related-business"
       :style="{'margin-left': marginLeft}">
    <!-- 新建- 关联业务 -->
    <el-popover v-model="showPopover"
                v-if="!isTask && alterable"
                placement="bottom"
                width="800"
                popper-class="no-padding-popover"
                trigger="click">
      <crm-relative v-if="showRelative"
                    ref="crmrelative"
                    :show="showPopover"
                    :radio="false"
                    :selectedData="relatedListData"
                    :showTypes='showTypes'
                    @close="crmrelativeClose"
                    @changeCheckout="checkInfos">
      </crm-relative>
      <p class="add-file"
         slot="reference"
         v-if="showCRMPermission"
         @click="showRelative = true">
        <img src="@/assets/img/relevance_business.png"
             alt="">
        关联业务
      </p>
    </el-popover>
    <p v-if="!alterable"
       class="alterable-p"
       :style="{color: alterableColor}">关联业务</p>
    <div v-for="(items, key) in relatedListData"
         :key="key">
      <related-business-cell v-for="(item, itemIndex) in items"
                             :data="item"
                             :cellIndex="itemIndex"
                             :type="key"
                             :key="itemIndex"
                             :showFoot="isTask"
                             @unbind="delRelevance"
                             @detail="checkRelatedDetail(key, item)">
      </related-business-cell>
    </div>
    <!-- 任务页面显示格式 -->
    <el-popover v-model="showPopover"
                v-if="isTask"
                placement="bottom"
                width="800"
                popper-class="no-padding-popover"
                trigger="click">
      <crm-relative ref="crmrelative"
                    v-if="showTaskRelative"
                    :radio="false"
                    :show="showPopover"
                    :selectedData="relatedListData"
                    :showTypes='showTypes'
                    @close="crmrelativeClose"
                    @changeCheckout="checkInfos">
      </crm-relative>
      <p v-if="showCRMPermission"
         class="add-file"
         @click="showTaskRelative = true"
         slot="reference">
        <img src="@/assets/img/relevance_business.png"
             alt="">
        关联业务
      </p>
    </el-popover>
  </div>
</template>

<script>
// 关联业务 - 弹出框
import { delrelation } from '@/api/oamanagement/task'
import CrmRelative from '@/components/CreateCom/CrmRelative'
import RelatedBusinessCell from '@/views/OAManagement/components/relatedBusinessCell'
import { objDeepCopy } from '@/utils'
import { mapGetters } from 'vuex'

export default {
  components: {
    CrmRelative,
    RelatedBusinessCell
  },
  data() {
    return {
      showTypes: ['customer', 'contacts', 'business', 'contract'],
      showPopover: false,
      relevanceAll: {
        customer_ids: [],
        contract_ids: [],
        contacts_ids: [],
        business_ids: []
      },
      // 关联业务信息
      relatedListData: {},
      showRelative: false,
      // 展示任务相关
      showTaskRelative: false
    }
  },
  computed: {
    ...mapGetters(['crm']),
    showCRMPermission() {
      return this.crm
    }
  },
  watch: {
    allData: function() {
      this.relatedListData = this.allData
    }
  },
  props: {
    marginLeft: {
      type: String,
      default: '20px'
    },
    // 编辑时传递所有关联数据   关联联系人-contacts 关联客户-customer 商机-business 合同-contract
    allData: {
      type: Object,
      default: () => {
        return {
          contacts: [],
          customer: [],
          business: [],
          contract: []
        }
      }
    },
    // 是否是任务页面
    isTask: {
      type: Boolean,
      default: false
    },
    taskID: Number,
    alterable: {
      type: Boolean,
      default: true
    },
    alterableColor: {
      type: String,
      default: '#999'
    }
  },
  mounted() {
    // 编辑时勾选
    this.relatedListData = this.allData
  },
  methods: {
    crmrelativeClose() {
      this.showPopover = false
    },
    checkInfos(val) {
      this.showPopover = false
      this.relatedListData = val.data
      for (let key in val.data) {
        const list = val.data[key]
        this.relevanceAll[key + '_ids'] = list.map(function(
          item,
          index,
          array
        ) {
          return item[key + '_id']
        })
      }
      this.$emit('checkInfos', this.relevanceAll)
    },
    // 任务页面取消关联
    delRelevance(field, index, item) {
      this.$confirm('确认取消关联?', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
        customClass: 'is-particulars'
      })
        .then(() => {
          delrelation({
            task_id: this.taskID,
            type: { customer: 1, contacts: 2, business: 3, contract: 4 }[field],
            id: item[field + '_id']
          })
            .then(res => {
              this.relatedListData[field].splice(index, 1)
              this.relatedListData = objDeepCopy(this.relatedListData)
              this.$message.success('关联取消成功')
            })
            .catch(() => {})
        })
        .catch(() => {
          this.$message.info('已取消操作')
        })
    },
    checkRelatedDetail(field, val) {
      val.key = val[field + '_id']
      this.$emit('checkRelatedDetail', field, val)
    }
  }
}
</script>

<style scoped lang="scss">
.related-business {
  color: #777;
  font-size: 12px;
  margin: 10px 0;
  .alterable-p {
    padding-bottom: 10px;
    font-size: 13px;
  }
  .add-file {
    margin: 10px 0;
    font-size: 13px;
    color: #3e84e9;
    cursor: pointer;
    display: inline-block;
    img {
      vertical-align: middle;
      width: 15px;
      margin-right: 5px;
    }
  }
}
</style>
