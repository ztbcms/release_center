<div id="app" style="padding: 8px;" v-cloak>
    <el-card>
        <div class="filter-container">
            <el-select v-model="listQuery.app_id" placeholder="所属应用" clearable filterable class="filter-item">
                <el-option v-for="item in appOptions" :key="item.id" :label="item.name + ' (' + item.app_key + ')'" :value="String(item.id)"></el-option>
            </el-select>
            <el-input v-model="listQuery.version" placeholder="Version" clearable class="filter-item"></el-input>
            <el-input v-model="listQuery.build" placeholder="Build" clearable class="filter-item small-filter"></el-input>
            <el-select v-model="listQuery.status" placeholder="状态" clearable class="filter-item small-filter">
                <el-option label="启用" value="1"></el-option>
                <el-option label="已撤回" value="2"></el-option>
            </el-select>
            <el-button type="primary" size="small" icon="el-icon-search" @click="handleFilter">搜索</el-button>
            <?php if (\app\admin\service\AdminUserService::getInstance()->hasPermission('release_center', 'admin.version', 'add')) { ?>
            <el-button type="primary" size="small" icon="el-icon-plus" @click="openForm()">发布版本</el-button>
            <?php } ?>
            <?php if (\app\admin\service\AdminUserService::getInstance()->hasPermission('release_center', 'admin.version', 'clearCache')) { ?>
            <el-button type="warning" size="small" icon="el-icon-delete" @click="clearCache">清理版本缓存</el-button>
            <?php } ?>
        </div>

        <el-table :data="list" v-loading="loading" highlight-current-row style="width: 100%;">
            <el-table-column label="应用" min-width="160">
                <template slot-scope="scope">
                    <div>{{ scope.row.app_name }}</div>
                    <div class="cell-tip">{{ scope.row.app_key }}</div>
                </template>
            </el-table-column>
            <el-table-column label="Version" prop="version" min-width="130"></el-table-column>
            <el-table-column label="Build" prop="build" width="100" align="center"></el-table-column>
            <el-table-column label="下载地址" min-width="240">
                <template slot-scope="scope"><a v-if="scope.row.download_url" :href="scope.row.download_url" target="_blank">{{ scope.row.download_url }}</a><span v-else>-</span></template>
            </el-table-column>
            <el-table-column label="发布时间" prop="publish_time_text" width="160"></el-table-column>
            <el-table-column label="状态" width="100" align="center">
                <template slot-scope="scope"><el-tag size="small" :type="scope.row.status == 1 ? 'success' : 'warning'">{{ scope.row.status_text }}</el-tag></template>
            </el-table-column>
            <el-table-column label="操作" width="180" fixed="right" align="center">
                <template slot-scope="scope">
                    <?php if (\app\admin\service\AdminUserService::getInstance()->hasPermission('release_center', 'admin.version', 'edit')) { ?>
                    <el-button type="text" size="mini" @click="openForm(scope.row)">编辑</el-button>
                    <?php } ?>
                    <?php if (\app\admin\service\AdminUserService::getInstance()->hasPermission('release_center', 'admin.version', 'changeStatus')) { ?>
                    <el-button v-if="scope.row.status == 1" type="text" size="mini" @click="changeStatus(scope.row, 2)">撤回</el-button>
                    <el-button v-else type="text" size="mini" @click="changeStatus(scope.row, 1)">恢复</el-button>
                    <?php } ?>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background layout="total, prev, pager, next, jumper" :total="total" :current-page.sync="listQuery.page" :page-size="listQuery.limit" @current-change="getList"></el-pagination>
        </div>
    </el-card>

    <el-dialog :title="form.id ? '编辑版本' : '发布版本'" :visible.sync="formVisible" width="680px" :close-on-click-modal="false">
        <el-form label-width="100px" :model="form">
            <el-form-item label="所属应用" required>
                <el-select v-model="form.app_id" :disabled="form.id > 0" placeholder="请选择启用应用" filterable style="width: 100%;" @change="selectApp">
                    <el-option v-for="item in enabledAppOptions" :key="item.id" :label="item.name + ' (' + item.app_key + ')'" :value="String(item.id)"></el-option>
                </el-select>
                <div v-if="!form.id && selectedApp" class="form-tip">当前最大 Build：{{ selectedApp.max_build }}，建议下一个 Build：{{ Number(selectedApp.max_build) + 1 }}</div>
            </el-form-item>
            <el-form-item label="Version" required>
                <el-input v-model="form.version" :disabled="form.id > 0" maxlength="100" placeholder="例如 1.2.0-beta"></el-input>
            </el-form-item>
            <el-form-item label="Build" required>
                <el-input v-model="form.build" :disabled="form.id > 0" placeholder="必须大于当前最大 Build"></el-input>
            </el-form-item>
            <el-form-item label="下载地址">
                <el-input v-model="form.download_url" maxlength="2048" placeholder="可选，填写时必须为 HTTP 或 HTTPS 地址"></el-input>
            </el-form-item>
            <el-form-item v-if="form.id" label="发布时间">
                <el-input v-model="form.publish_time_text" disabled></el-input>
            </el-form-item>
            <el-form-item label="更新说明">
                <el-input v-model="form.release_note" type="textarea" :rows="5" placeholder="可选"></el-input>
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
    .filter-item { width: 190px; margin-right: 8px; margin-bottom: 8px; }
    .small-filter { width: 120px; }
    .pagination-container { padding: 24px 0 0; text-align: right; }
    .cell-tip, .form-tip { color: #909399; font-size: 12px; line-height: 18px; }
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
                appOptions: [],
                selectedApp: null,
                formVisible: false,
                form: {id: 0, app_id: '', version: '', build: '', download_url: '', release_note: '', publish_time_text: ''},
                listQuery: {page: 1, limit: 20, app_id: '', version: '', build: '', status: ''}
            },
            computed: {
                enabledAppOptions: function () {
                    return this.appOptions.filter(function (item) { return item.status === 1; });
                }
            },
            methods: {
                getAppOptions: function () {
                    var that = this;
                    $.ajax({
                        url: "{:api_url('/release_center/admin.version/getAppOptions')}",
                        type: 'get',
                        dataType: 'json',
                        success: function (res) {
                            if (res.status) { that.appOptions = res.data.items; }
                            else { that.$message.error(res.msg); }
                        }
                    });
                },
                handleFilter: function () {
                    this.listQuery.page = 1;
                    this.getList();
                },
                getList: function () {
                    var that = this;
                    that.loading = true;
                    $.ajax({
                        url: "{:api_url('/release_center/admin.version/getList')}",
                        type: 'get',
                        dataType: 'json',
                        data: that.listQuery,
                        success: function (res) {
                            if (res.status) {
                                that.list = res.data.items;
                                that.total = res.data.total;
                            } else { that.$message.error(res.msg); }
                        },
                        complete: function () { that.loading = false; }
                    });
                },
                openForm: function (row) {
                    this.form = {
                        id: row ? row.id : 0,
                        app_id: row ? String(row.app_id) : '',
                        version: row ? row.version : '',
                        build: row ? String(row.build) : '',
                        download_url: row ? row.download_url : '',
                        release_note: row ? row.release_note : '',
                        publish_time_text: row ? row.publish_time_text : ''
                    };
                    this.selectApp(this.form.app_id);
                    this.formVisible = true;
                },
                selectApp: function (appId) {
                    var selected = null;
                    for (var i = 0; i < this.appOptions.length; i++) {
                        if (String(this.appOptions[i].id) === String(appId)) { selected = this.appOptions[i]; break; }
                    }
                    this.selectedApp = selected;
                },
                saveForm: function () {
                    var that = this;
                    var url = that.form.id ? "{:api_url('/release_center/admin.version/edit')}" : "{:api_url('/release_center/admin.version/add')}";
                    that.saving = true;
                    that.httpPost(url, that.form, function (res) {
                        if (res.status) {
                            that.$message.success(res.msg);
                            that.formVisible = false;
                            that.getList();
                            that.getAppOptions();
                        } else { that.$message.error(res.msg); }
                        that.saving = false;
                    });
                },
                changeStatus: function (row, status) {
                    var that = this;
                    var text = status === 1 ? '恢复' : '撤回';
                    layer.confirm('确认' + text + ' Version ' + row.version + '（Build ' + row.build + '）？', {btn: ['确认', '取消']}, function () {
                        that.httpPost("{:api_url('/release_center/admin.version/changeStatus')}", {id: row.id, status: status}, function (res) {
                            if (res.status) { that.$message.success(res.msg); that.getList(); }
                            else { that.$message.error(res.msg); }
                        });
                        layer.closeAll();
                    });
                },
                clearCache: function () {
                    var that = this;
                    layer.confirm('确认清理全部应用的版本缓存？清理后下一次客户端检查将读取最新发布状态。', {btn: ['确认', '取消']}, function () {
                        that.httpPost("{:api_url('/release_center/admin.version/clearCache')}", {}, function (res) {
                            if (res.status) { that.$message.success(res.msg); }
                            else { that.$message.error(res.msg); }
                        });
                        layer.closeAll();
                    });
                }
            },
            mounted: function () { this.getAppOptions(); this.getList(); }
        });
    });
</script>
