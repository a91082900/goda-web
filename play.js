var vm = new Vue({
    el: '#app',
    vuetify: new Vuetify(),
    data: {
        bill: [],
        /*headers: [{
                text: "借入",
                value: 'Borrower',
            },
            {
                text: "借出",
                value: 'Lender',
            },
            {
                text: "金額",
                value: 'Dollar',
            },
            {
                text: '',
                value: 'data-table-expand',
                sortable: false, 
                align: "right"
            }
        ],*/
        selected: [],
        windowWidth: window.innerWidth, 
        windowHeight: window.innerHeight,
        users: {},
        dialog: false, 
        addBill: false,
        editData: { 
            Borrower: '', 
            Lender: '', 
            Dollar: '', 
            Date: '', 
            Thing: '', 
            menu: false, 
            menu_date: false, 
            formValid: false
        }, 
        newData: { 
            Borrower: '', 
            Lender: '', 
            Dollar: '', 
            Date: '', 
            Thing: '', 
        }, 
        confirm: false, 
        read_type: "0", // 0: unsettled, 1: settled 
        menu1: false, 
        repay: [], 
        rules: {
            userRule: function(data) {
                return [data.Borrower != data.Lender || "借入與借出為同一人"];
            },
            dollarRule: [
                v => !isNaN(v) && v > 0 || "請輸入大於0的整數", 
                v => !(v.toString().includes("e")) || "請勿使用科學記號"
            ], 
            thingRule: [v => !!v || '請輸入事由'], 
            dateRule: [v => !!v || '請選擇日期']
        }, 
        formValid: false,
        snackbar: {show: false, text: "Hi", color: "success"},
        user_filter: []
    },
    methods: {
        toggleSelect: function(item, event) {
            var idx = this.selected.indexOf(item);
            if (idx != -1) {
                this.selected.splice(idx, 1);
            } else {
                this.selected.push(item);
            }

        },
        

        allSelect: function() {
            if(this.selected.length == this.bill.length) {
                this.clearSelect();
                return;
            }
            this.selected = this.bill.slice();
        },
        clearSelect: function() {
            this.selected = [];
        },
        new_clicked: function(text) {
            var snackbar = new mdc.snackbar.MDCSnackbar(document.querySelector('.mdc-snackbar'));
            snackbar.labelText = text;
            snackbar.open();
        },
        save: function(item) {
            item.menu = false;
            var vm = this;

            this.postData({
                action: "edit_bill", 
                borrower: vm.editData.Borrower,
                lender: vm.editData.Lender,
                date: vm.editData.Date,
                thing: vm.editData.Thing,
                dollar: vm.editData.Dollar, 
                edit_item_id: item.Bill_ID
            }).then(function(data) {
                if(data) {
                    for (var key in vm.editData) {
                        item[key] = vm.editData[key];
                    }
                    vm.new_clicked("完成");
                }
                else
                    vm.new_clicked("失敗");
            });

            
        },
        del: function(item) {
            this.confirm = false;
            item.menu = false;
            var idx = this.bill.indexOf(item);
            if(idx > -1) {
                this.postData({
                    action: "delete",
                    ids: [item.Bill_ID,]
                }).then(function(data) {
                    if(data) {
                        vm.bill.splice(idx, 1);

                        idx = this.selected.indexOf(item);
                        if(idx > -1)
                            this.selected.splice(idx, 1);
                        vm.new_clicked("完成");
                    }
                });
            }
                
            
        },
        saveNew: function() {
            this.addBill = false;
            var vm = this;
            this.postData({
                action: "add_bill", 
                borrower: this.newData.Borrower,
                lender: this.newData.Lender,
                date: this.newData.Date,
                thing: this.newData.Thing,
                dollar: this.newData.Dollar
            }).then(function(data) {
                if(data) {
                    vm.bill.push(vm.newData);
                    vm.new_clicked("完成");
                }
                else
                    vm.new_clicked("失敗");
                vm.newData = { 
                    Borrower: '', 
                    Lender: '', 
                    Dollar: '', 
                    Date: '', 
                    Thing: '', 
                };
            });

            
            this.$refs.newForm.resetValidation();
        },
        computeRepay: function() {
            if(this.read_type == "1") {
                this.repay = [];
                return;
            }
            var data = this.selected;
            var users = this.users;

            var bill_array = {};
            for(var user_id in users) {
                bill_array[user_id] = 0;
            }
            data.forEach(function(datum){
                bill_array[datum.Borrower] -= datum.Dollar;
                bill_array[datum.Lender] += datum.Dollar;
            });
        
            //console.log(bill_array);

            var repay = Array();
        
            while(!all_zero(bill_array)) {
                console.log(bill_array);
                var [max, min] = getExtreme(bill_array);
                console.log(max,bill_array[max], min, bill_array[min]);
                if(bill_array[max] >= bill_array[min] * -1) {
                    repay.push(Array(min, max, bill_array[min] * -1));
        
                    bill_array[max] += bill_array[min];
                    bill_array[min] = 0;
                }
                else {
                    repay.push(Array(min, max, bill_array[max]));
        
                    bill_array[min] += bill_array[max];
                    bill_array[max] = 0;
                }
            }

            this.repay = repay;
        }, 
        settle: function() {
            this.dialog = false;
            
            var vm = this;
            // TODO: Make settled list
            var ids = [];
            this.selected.forEach(item => ids.push(item.Bill_ID));

            this.postData({
                action: parseInt(this.read_type) ? "unreturn" : "return", 
                ids: ids
            }).then(function(data) {
                if(data) {
                    vm.selected.forEach(function(item){
                        var idx = vm.bill.indexOf(item);
                        if (idx != -1) {
                            vm.bill.splice(idx, 1);
                        } 
                    });
                    vm.selected = [];
                    vm.new_clicked("完成");
                }
                else
                    vm.new_clicked("失敗");
            });
        }, 
        postData: async function(data = {}) {
            var vm = this;
            var arr = Array();
            for(var d in data) {
                if(typeof data[d] == "object") {
                    for(var dd in data[d]) {
                        arr.push(d+"[]="+data[d][dd]);
                    }
                }
                else
                    arr.push(d+"="+data[d]);
            }
            console.log(arr.join("&"));
            // Default options are marked with *
            const response = await fetch("post.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: arr.join("&")
            });
            return response.json();
        }, 
        load_detail: function(load_settled) {
            var vm = this;
            vm.selected = [];
            var post = {action: "load_detail", load_settled: load_settled, users: vm.user_filter};
            vm.postData(post).then(function(data){
                console.log(data);

                data.forEach(function(item){
                    item["menu"] = false;
                    item["menu_date"] = false;
                });
                
                vm.bill = data;    
            });
            
        }
    },
    computed: {
        isMobile: function() {
            return this.windowWidth < 450;
        },
        headers: function() {
            if(this.isMobile) {
                headers = [
                    {
                        text: "借入",
                        value: 'Borrower',
                    },
                    {
                        text: "借出",
                        value: 'Lender',
                    },
                    {
                        text: "金額",
                        value: 'Dollar',
                    },
                    {
                        text: '',
                        value: 'data-table-expand',
                        sortable: false, 
                        align: "right"
                    }
                ];
            }
            else {
                headers =  [
                    {
                        text: "借入",
                        value: 'Borrower',
                    },
                    {
                        text: "借出",
                        value: 'Lender',
                    },
                    {
                        text: "金額",
                        value: 'Dollar',
                    },
                    {
                        text: "事由",
                        value: 'Thing',
                    },
                    {
                        text: "日期",
                        value: 'Date',
                    },
                    {
                        text: '',
                        value: 'data-table-expand',
                        sortable: false, 
                        align: "right"
                    },
                ];
            }
            return headers;
        },
        user_select: function() {
            var user_select = [];
            for(var user_id in this.users) {
                user_select.push({
                    text: this.users[user_id], 
                    value: parseInt(user_id)
                });
            }
            return user_select;
        }, 
    },
    mounted() {
        var vm = this;
        window.addEventListener('resize', function() {
            vm.windowWidth = window.innerWidth;
            vm.windowHeight = window.innerHeight;
        });
        vm.postData({action: "load_users"}).then(function(data){
            vm.users = data;
            vm.user_filter = Object.keys(data);
        }).then(function() {
            vm.load_detail(0);
        });
    },
});

function getExtreme(data) {
    var max = Object.keys(data).reduce((prev, curr) =>
        data[prev] > data[curr] ? prev : curr
    );

    var min = Object.keys(data).reduce((prev, curr) =>
        data[prev] < data[curr] ? prev : curr
    );

    return [max, min];
}
function all_zero(data) {
    return Object.keys(data).every((key) => data[key] == 0);
}
