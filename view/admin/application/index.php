<div id="app" style="padding: 8px;" v-cloak>
    <el-card>
        <div class="filter-container">
            <el-input v-model="listQuery.name" placeholder="应用名称" clearable class="filter-item"></el-input>
            <el-input v-model="listQuery.app_key" placeholder="AppKey" clearable class="filter-item"></el-input>
            <el-select v-model="listQuery.status" placeholder="状态" clearable class="filter-item small-filter">
                <el-option label="启用" value="1"></el-option>
                <el-option label="禁用" value="2"></el-option>
            </el-select>
            <el-button type="primary" size="small" icon="el-icon-search" @click="handleFilter">搜索</el-button>
            <?php if (\app\admin\service\AdminUserService::getInstance()->hasPermission('release_center', 'admin.application', 'add')) { ?>
            <el-button type="primary" size="small" icon="el-icon-plus" @click="openForm()">新增应用</el-button>
            <?php } ?>
        </div>

        <el-table :data="list" v-loading="loading" highlight-current-row style="width: 100%;">
            <el-table-column label="应用名称" prop="name" min-width="160"></el-table-column>
            <el-table-column label="AppKey" prop="app_key" min-width="180"></el-table-column>
            <el-table-column label="备注" prop="remark" min-width="200"></el-table-column>
            <el-table-column label="状态" width="100" align="center">
                <template slot-scope="scope">
                    <el-tag size="small" :type="scope.row.status == 1 ? 'success' : 'warning'">{{ scope.row.status_text }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="创建时间" width="160" prop="create_time_text"></el-table-column>
            <el-table-column label="更新时间" width="160" prop="update_time_text"></el-table-column>
            <el-table-column label="操作" width="180" fixed="right" align="center">
                <template slot-scope="scope">
                    <?php if (\app\admin\service\AdminUserService::getInstance()->hasPermission('release_center', 'admin.application', 'edit')) { ?>
                    <el-button type="text" size="mini" @click="openForm(scope.row)">编辑</el-button>
                    <?php } ?>
                    <?php if (\app\admin\service\AdminUserService::getInstance()->hasPermission('release_center', 'admin.application', 'changeStatus')) { ?>
                    <el-button v-if="scope.row.status == 1" type="text" size="mini" @click="changeStatus(scope.row, 2)">禁用</el-button>
                    <el-button v-else type="text" size="mini" @click="changeStatus(scope.row, 1)">启用</el-button>
                    <?php } ?>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background layout="total, prev, pager, next, jumper" :total="total" :current-page.sync="listQuery.page" :page-size="listQuery.limit" @current-change="getList"></el-pagination>
        </div>
    </el-card>

    <el-dialog :title="form.id ? '编辑应用' : '新增应用'" :visible.sync="formVisible" width="560px" :close-on-click-modal="false">
        <el-form label-width="90px" :model="form">
            <el-form-item label="应用名称" required>
                <el-input v-model="form.name" maxlength="100" show-word-limit placeholder="请输入应用名称"></el-input>
            </el-form-item>
            <el-form-item label="AppKey" required>
                <el-input v-model="form.app_key" :disabled="form.id > 0" maxlength="64" placeholder="例如 mall_app"></el-input>
                <div class="form-tip">仅支持小写字母、数字、下划线和中划线，创建后不可修改</div>
            </el-form-item>
            <el-form-item label="备注">
                <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="500" show-word-limit placeholder="可选"></el-input>
            </el-form-item>
        </el-form>
        <span slot="footer" class="dialog-footer">
            <el-button @click="formVisible = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="saveForm">保存</el-button>
        </span>
    </el-dialog>
</div>

<style>
    .filter-container { padding-bottom: 12px; }
    .filter-item { width: 200px; margin-right: 8px; margin-bottom: 8px; }
    .small-filter { width: 120px; }
    .pagination-container { padding: 24px 0 0; text-align: right; }
    .form-tip { color: #909399; font-size: 12px; line-height: 18px; }
</style>

<script>
    $(document).ready(function () {
        new Vue({
            el: '#app',
            data: {
                loading: false,
                saving: false,
                list: [],
                total: 0,
                formVisible: false,
                form: {id: 0, name: '', app_key: '', remark: ''},
                listQuery: {page: 1, limit: 20, name: '', app_key: '', status: ''}
            },
            methods: {
                handleFilter: function () {
                    this.listQuery.page = 1;
                    this.getList();
                },
                getList: function () {
                    var that = this;
                    that.loading = true;
                    $.ajax({
                        url: "{:api_url('/release_center/admin.application/getList')}",
                        type: 'get',
                        dataType: 'json',
                        data: that.listQuery,
                        success: function (res) {
                            if (res.status) {
                                that.list = res.data.items;
                                that.total = res.data.total;
                            } else {
                                that.$message.error(res.msg);
                            }
                        },
                        complete: function () { that.loading = false; }
                    });
                },
                openForm: function (row) {
                    this.form = {
                        id: row ? row.id : 0,
                        name: row ? row.name : '',
                        app_key: row ? row.app_key : '',
                        remark: row ? row.remark : ''
                    };
                    this.formVisible = true;
                },
                saveForm: function () {
                    var that = this;
                    var url = that.form.id ? "{:api_url('/release_center/admin.application/edit')}" : "{:api_url('/release_center/admin.application/add')}";
                    that.saving = true;
                    that.httpPost(url, that.form, function (res) {
                        if (res.status) {
                            that.$message.success(res.msg);
                            that.formVisible = false;
                            that.getList();
                        } else {
                            that.$message.error(res.msg);
                        }
                        that.saving = false;
                    });
                },
                changeStatus: function (row, status) {
                    var that = this;
                    var text = status === 1 ? '启用' : '禁用';
                    layer.confirm('确认' + text + '应用“' + row.name + '”？', {btn: ['确认', '取消']}, function () {
                        that.httpPost("{:api_url('/release_center/admin.application/changeStatus')}", {id: row.id, status: status}, function (res) {
                            if (res.status) {
                                that.$message.success(res.msg);
                                that.getList();
                            } else {
                                that.$message.error(res.msg);
                            }
                        });
                        layer.closeAll();
                    });
                }
            },
            mounted: function () { this.getList(); }
        });
    });
</script>
